#!/usr/bin/env python3
"""Leadership portraits: transparent cutouts on one shared 4:5 frame.

What it does
  Reads the photos in media/src/people and writes one cutout per person to
  wp-content/plugins/audazzio-core/assets/people/<slug>.webp (1000x1250, WebP with alpha).
  Every cutout uses the same face scale, the head top sits near 13% from the top, the face is
  centred, and the body runs off the bottom edge. A review sheet (all six on #F2F3F5 and on
  white) goes to media/work/portraits-sheet.png.

  Steps per person: tidy compression blocks (chroma guided filter, light luma denoise when
  needed) -> upscale small sources (OpenCV dnn_superres EDSR, Lanczos + unsharp as fallback)
  -> matte (BiRefNet-portrait ONNX; falls back to isnet-general-use, then u2net_human_seg)
  -> choke the edge and re-estimate edge colour (pymatting) so no old background or halo
  survives -> find the face (OpenCV YuNet) -> scale and place on the canvas -> grade lightly.

What it needs
  python3 with numpy, pillow (WebP), opencv-contrib-python-headless, onnxruntime, pymatting.
  They can live in any folder: pip install --target DIR ... then pass --pypath DIR (or set
  PORTRAITS_PYPATH). Models download on first run into --models (default
  ~/.cache/audazzio-portraits/models, about 1.2 GB): BiRefNet-portrait (MIT), EDSR x2/x3/x4,
  YuNet (MIT). About 2 minutes per person on 4 CPU cores, 6 GB RAM peak.

Run
  python3 -I scripts/media/portraits.py --pypath DIR [--models DIR] [--cache DIR] [--only slug,slug]
  Add --alt larry-mills to build Larry from the larger HOLT CAT photo instead (see PEOPLE).
"""
import os
import sys


def _early_pypath():
    paths = PYPATHS
    if os.environ.get("PORTRAITS_PYPATH"):
        paths += os.environ["PORTRAITS_PYPATH"].split(os.pathsep)
    argv = sys.argv
    for i, a in enumerate(argv):
        if a == "--pypath" and i + 1 < len(argv):
            paths += argv[i + 1].split(os.pathsep)
        elif a.startswith("--pypath="):
            paths += a.split("=", 1)[1].split(os.pathsep)
    for p in reversed(paths):
        if p and p not in sys.path:
            sys.path.insert(0, p)


PYPATHS = []
_early_pypath()

import argparse  # noqa: E402
import hashlib  # noqa: E402
import json  # noqa: E402
import shutil  # noqa: E402
import subprocess  # noqa: E402
import tempfile  # noqa: E402
import urllib.request  # noqa: E402

import numpy as np  # noqa: E402
from PIL import Image, ImageDraw, ImageFont  # noqa: E402

ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), "..", ".."))
SRC = os.path.join(ROOT, "media", "src", "people")
OUT = os.path.join(ROOT, "wp-content", "plugins", "audazzio-core", "assets", "people")
SHEET = os.path.join(ROOT, "media", "work", "portraits-sheet.png")

# Canvas and framing.
W, H = 1000, 1250
CROWN_Y = 0.13 * H          # top of head
EYE_WEIGHT = 0.5            # 0 = align head tops only, 1 = align eye lines only
MIN_CROWN_Y = 0.07 * H      # never push hair closer to the top than this
BOTTOM_SLACK = 4            # px the body must overshoot the bottom edge

# Very light grade shared by everyone: a touch of contrast, a touch less saturation.
GRADE = dict(contrast=0.04, saturation=0.96)

GH = "https://github.com/danielgatis/rembg/releases/download/v0.0.0/"
SR = "https://raw.githubusercontent.com/Saafke/EDSR_Tensorflow/master/models/"
MODELS = {
    "birefnet-portrait": (GH + "BiRefNet-portrait-epoch_150.onnx", "imagenet", True),
    "isnet-general-use": (GH + "isnet-general-use.onnx", "half", False),
    "u2net_human_seg": (GH + "u2net_human_seg.onnx", "imagenet", False),
}
MATTE_ORDER = ["birefnet-portrait", "isnet-general-use", "u2net_human_seg"]
YUNET = "https://media.githubusercontent.com/media/opencv/opencv_zoo/main/models/face_detection_yunet/face_detection_yunet_2023mar.onnx"

# Per person. src is what audazzio.com uses today, or a larger copy of the same photo.
#   trim     (left, top, right, bottom) px to cut from the source first
#   scale    upscale factor for the working copy (EDSR when 2, 3 or 4), or < 1 to shrink
#   luma     non-local-means strength on luma before upscaling (0 = off)
#   chroma   guided-filter radius for chroma before upscaling (0 = off)
#   erode    px (working copy) the matte is pulled in, to drop old halos
#   extend   a second, wider crop of the same photo, registered and used where src stops
PEOPLE = [
    dict(slug="roy-terracina", name="Roy Terracina", src="roy_terracina.png", trim=(0, 0, 1, 0),
         scale=4, luma=0, chroma=2, erode=3),
    dict(slug="michele-klumb", name="Michele Klumb", src="michele_klumb.png", trim=(0, 0, 1, 0),
         scale=4, luma=0, chroma=2, erode=3),
    dict(slug="bryan-elliot", name="Bryan Elliot", src="bryan_elliot.jpg",
         scale=0.6, luma=0, chroma=0, erode=1),
    dict(slug="greg-flores", name="Greg Flores", src="greg_flores_shrm.png", extend="greg_flores.png",
         scale=4, luma=0, chroma=3, erode=1),
    dict(slug="alan-c-gottlob", name="Alan C. Gottlob", src="alan_gottlob.jpg",
         scale=2, luma=0, chroma=2, erode=1),
    dict(slug="larry-mills", name="Larry Mills", src="larry_mills.webp",
         scale=4, luma=3, chroma=4, erode=1,
         # Probably the same man years later, but not certain, so it is opt-in only.
         alt=dict(src="larry_mills_holtcat.jpg", scale=2, luma=0, chroma=2, erode=1)),
]


def log(*a):
    print(*a, flush=True)


# ----------------------------------------------------------------------------- downloads

def fetch(url, path):
    if os.path.exists(path) and os.path.getsize(path) > 10000:
        return path
    os.makedirs(os.path.dirname(path), exist_ok=True)
    tmp = path + ".part"
    log(f"  downloading {os.path.basename(path)}")
    try:
        with urllib.request.urlopen(url, timeout=120) as r, open(tmp, "wb") as f:
            shutil.copyfileobj(r, f, 1 << 20)
    except Exception as e:  # urllib can trip on some proxies; curl usually does not
        log(f"  urllib failed ({e}); trying curl")
        subprocess.run(["curl", "-sSLf", "-o", tmp, url], check=True)
    if os.path.getsize(tmp) < 10000:
        raise RuntimeError(f"download too small, probably an error page: {url}")
    os.replace(tmp, path)
    return path


# ----------------------------------------------------------------------------- matting

def matte_worker(model_name, model_path, in_png, out_png):
    """Runs in a child process so each model's memory is returned to the OS."""
    import cv2
    import onnxruntime as ort
    _, norm, sigmoid = MODELS[model_name]
    so = ort.SessionOptions()
    so.enable_cpu_mem_arena = False
    so.enable_mem_pattern = False
    so.log_severity_level = 3
    sess = ort.InferenceSession(model_path, so, providers=["CPUExecutionProvider"])
    inp = sess.get_inputs()[0]
    side = inp.shape[2] if isinstance(inp.shape[2], int) else 1024
    img = cv2.cvtColor(cv2.imread(in_png), cv2.COLOR_BGR2RGB)
    x = cv2.resize(img, (side, side), interpolation=cv2.INTER_AREA).astype(np.float32) / 255.0
    if norm == "imagenet":
        x = (x - np.array([0.485, 0.456, 0.406], np.float32)) / np.array([0.229, 0.224, 0.225], np.float32)
    else:
        x = x - 0.5
    p = sess.run(None, {inp.name: x.transpose(2, 0, 1)[None].astype(np.float32)})[0][0, 0]
    if sigmoid:
        p = 1.0 / (1.0 + np.exp(-p))
    else:
        p = (p - p.min()) / max(p.max() - p.min(), 1e-6)
    p = cv2.resize(p.astype(np.float32), (img.shape[1], img.shape[0]), interpolation=cv2.INTER_CUBIC)
    cv2.imwrite(out_png, (np.clip(p, 0, 1) * 65535).astype(np.uint16))


def matte(in_png, out_png, models_dir):
    if os.path.exists(out_png):
        return out_png, "cached"
    last = None
    for name in MATTE_ORDER:
        url = MODELS[name][0]
        try:
            path = fetch(url, os.path.join(models_dir, "matte", os.path.basename(url)))
            subprocess.run([sys.executable, "-I", os.path.abspath(__file__), "--pypath", os.pathsep.join(PYPATHS),
                            "--_matte", name, path, in_png, out_png], check=True)
            return out_png, name
        except Exception as e:
            last = e
            log(f"  matte model {name} failed: {e}; trying the next one")
    raise RuntimeError(f"no matting model worked: {last}")


# ----------------------------------------------------------------------------- image steps

def load_rgb(path, trim=(0, 0, 0, 0)):
    im = Image.open(path)
    im.load()
    if im.mode in ("RGBA", "LA", "P"):
        im = im.convert("RGBA")
        bg = Image.new("RGBA", im.size, (255, 255, 255, 255))
        im = Image.alpha_composite(bg, im)
    im = im.convert("RGB")
    l, t, r, b = trim
    im = im.crop((l, t, im.width - r, im.height - b))
    import cv2
    return cv2.cvtColor(np.asarray(im), cv2.COLOR_RGB2BGR)


def deblock(img, luma, chroma):
    """Soften 8x8 blocks and smeared chroma from heavy JPEG/WebP compression."""
    import cv2
    if not luma and not chroma:
        return img
    y, cr, cb = cv2.split(cv2.cvtColor(img, cv2.COLOR_BGR2YCrCb))
    if luma:
        y = cv2.fastNlMeansDenoising(y, None, luma, 5, 13)
    if chroma:
        cr = cv2.ximgproc.guidedFilter(y, cr, chroma, 60)
        cb = cv2.ximgproc.guidedFilter(y, cb, chroma, 60)
    return cv2.cvtColor(cv2.merge([y, cr, cb]), cv2.COLOR_YCrCb2BGR)


def upscale(img, k, models_dir):
    import cv2
    if k < 1:
        return cv2.resize(img, None, fx=k, fy=k, interpolation=cv2.INTER_AREA), "area"
    if k == 1:
        return img, "none"
    if int(k) == k and int(k) in (2, 3, 4):
        try:
            path = fetch(SR + f"EDSR_x{int(k)}.pb", os.path.join(models_dir, "sr", f"EDSR_x{int(k)}.pb"))
            sr = cv2.dnn_superres.DnnSuperResImpl_create()
            sr.readModel(path)
            sr.setModel("edsr", int(k))
            return sr.upsample(img), f"EDSR x{int(k)}"
        except Exception as e:
            log(f"  EDSR unavailable ({e}); using Lanczos + unsharp mask")
    up = cv2.resize(img, None, fx=k, fy=k, interpolation=cv2.INTER_LANCZOS4)
    blur = cv2.GaussianBlur(up, (0, 0), 0.6 * k)
    return cv2.addWeighted(up, 1.35, blur, -0.35, 0), f"Lanczos x{k} + unsharp"


def extend_with(base, wide):
    """Register `wide` (a looser crop of the same photo) onto `base` and use it where base stops."""
    import cv2
    sift = cv2.SIFT_create(4000)
    ka, da = sift.detectAndCompute(cv2.cvtColor(base, cv2.COLOR_BGR2GRAY), None)
    kb, db = sift.detectAndCompute(cv2.cvtColor(wide, cv2.COLOR_BGR2GRAY), None)
    pairs = [m for m, n in cv2.BFMatcher().knnMatch(db, da, k=2) if m.distance < 0.75 * n.distance]
    pb = np.float32([kb[m.queryIdx].pt for m in pairs])
    pa = np.float32([ka[m.trainIdx].pt for m in pairs])
    M, inl = cv2.estimateAffinePartial2D(pb, pa, method=cv2.RANSAC, ransacReprojThreshold=3)
    if M is None or inl.sum() < 40:
        raise RuntimeError("could not register the two crops")
    hb, wb = wide.shape[:2]
    c = np.float32([[0, 0], [wb, 0], [0, hb], [wb, hb]]) @ M[:, :2].T + M[:, 2]
    x0, y0 = min(0, int(np.floor(c[:, 0].min()))), min(0, int(np.floor(c[:, 1].min())))
    x1 = max(base.shape[1], int(np.ceil(c[:, 0].max())))
    y1 = max(base.shape[0], int(np.ceil(c[:, 1].max())))
    T = M.copy()
    T[:, 2] -= (x0, y0)
    size = (x1 - x0, y1 - y0)
    warped = cv2.warpAffine(wide, T, size, flags=cv2.INTER_LANCZOS4, borderMode=cv2.BORDER_REPLICATE)
    canvas = warped.astype(np.float32)
    region = (slice(-y0, -y0 + base.shape[0]), slice(-x0, -x0 + base.shape[1]))
    # Match the wide crop's colour to the base where they overlap (per channel, linear).
    ov = canvas[region].reshape(-1, 3)
    bs = base.reshape(-1, 3).astype(np.float32)
    for ch in range(3):
        g = bs[:, ch].std() / max(ov[:, ch].std(), 1e-3)
        canvas[..., ch] = (canvas[..., ch] - ov[:, ch].mean()) * g + bs[:, ch].mean()
    # Feathered paste of the base, so the seam is invisible.
    m = np.zeros(size[::-1], np.float32)
    m[region] = 1.0
    feather = max(8, int(0.03 * max(base.shape)))
    inner = cv2.erode(m, np.ones((3, 3), np.uint8), iterations=feather, borderValue=1)
    inner = cv2.GaussianBlur(inner, (0, 0), feather / 2.5)
    inner = np.minimum(inner, m)[..., None]
    out = canvas * (1 - inner)
    out[region] += base.astype(np.float32) * inner[region]
    out = np.clip(out, 0, 255).astype(np.uint8)
    info = dict(offset=(-x0, -y0), scale=float(np.hypot(M[0, 0], M[1, 0])), inliers=int(inl.sum()),
                grow=dict(left=-x0, top=-y0, right=x1 - base.shape[1], bottom=y1 - base.shape[0]))
    return out, info


def refine(img_bgr, alpha, erode):
    """Pull the matte in a little and recover clean edge colour (no halo, no old background)."""
    import cv2
    from pymatting import estimate_foreground_ml
    a = alpha.astype(np.float32)
    if erode:
        k = cv2.getStructuringElement(cv2.MORPH_ELLIPSE, (2 * erode + 1, 2 * erode + 1))
        a = cv2.erode(a, k)  # borders count as foreground, so image edges stay solid
    a = np.clip((a - 0.10) / 0.82, 0, 1)
    a = cv2.GaussianBlur(a, (0, 0), 0.6)
    a[a < 0.02] = 0
    a[a > 0.985] = 1
    rgb = cv2.cvtColor(img_bgr, cv2.COLOR_BGR2RGB).astype(np.float64) / 255.0
    fg = estimate_foreground_ml(rgb, a.astype(np.float64))
    return np.clip(fg, 0, 1).astype(np.float32), a


def face(img_bgr, alpha, models_dir):
    import cv2
    path = fetch(YUNET, os.path.join(models_dir, "face", "yunet_2023mar.onnx"))
    h, w = img_bgr.shape[:2]
    s = 640.0 / max(h, w)
    small = cv2.resize(img_bgr, None, fx=s, fy=s, interpolation=cv2.INTER_AREA)
    det = cv2.FaceDetectorYN.create(path, "", (small.shape[1], small.shape[0]), 0.6)
    _, faces = det.detect(small)
    if faces is None or not len(faces):
        raise RuntimeError("no face found")
    f = max(faces, key=lambda r: r[2] * r[3]) / s
    bx, by, bw, bh = f[:4]
    eye_r, eye_l, mouth_r, mouth_l = f[4:6], f[6:8], f[10:12], f[12:14]
    eyes = (eye_r + eye_l) / 2
    mouth = (mouth_r + mouth_l) / 2
    # Face scale: average of three estimates so a big smile or a turned head does not skew it.
    S = (np.linalg.norm(eye_r - eye_l) + np.linalg.norm(mouth - eyes) + bh / 3.0) / 3.0
    cx = bx + bw / 2
    band = alpha[:, max(0, int(cx - 0.45 * bw)):int(cx + 0.45 * bw)]
    crown = int(np.argmax((band > 0.5).any(axis=1)))
    return dict(S=float(S), cx=float(cx), eyes_y=float(eyes[1]), crown=crown)


def grade(rgb):
    lum = rgb @ np.array([0.2126, 0.7152, 0.0722], np.float32)
    out = lum[..., None] + (rgb - lum[..., None]) * GRADE["saturation"]
    out = 0.5 + (out - 0.5) * (1 + GRADE["contrast"])
    return np.clip(out, 0, 1)


# ----------------------------------------------------------------------------- pipeline

def prepare(p, models_dir, cache):
    """Clean, upscale, matte and refine one person. Returns float RGB fg, alpha, face info."""
    import cv2
    key = hashlib.sha1(json.dumps({k: p.get(k) for k in ("src", "trim", "scale", "luma", "chroma", "extend")},
                                  sort_keys=True).encode()).hexdigest()[:10]
    base = os.path.join(cache, f"{p['slug']}-{key}")
    up_png, mask_png = base + "-up.png", base + "-matte.png"
    notes = []
    if not os.path.exists(up_png):
        img = deblock(load_rgb(os.path.join(SRC, p["src"]), p.get("trim", (0, 0, 0, 0))), p["luma"], p["chroma"])
        img, how = upscale(img, p["scale"], models_dir)
        notes.append(how)
        if p.get("extend"):
            wide = deblock(load_rgb(os.path.join(SRC, p["extend"])), p["luma"], p["chroma"])
            wide, _ = upscale(wide, p["scale"], models_dir)
            img, info = extend_with(img, wide)
            notes.append(f"extended with {p['extend']} {info['grow']}")
        cv2.imwrite(up_png, img)
    img = cv2.imread(up_png)
    _, used = matte(up_png, mask_png, models_dir)
    notes.append(f"matte {used}")
    alpha = cv2.imread(mask_png, cv2.IMREAD_UNCHANGED).astype(np.float32) / 65535.0
    fg, a = refine(img, alpha, p["erode"])
    info = face(img, a, models_dir)
    # How far below the head the source runs, and whether the body is cut by each source edge.
    h, w = a.shape
    info["below"] = h - info["crown"]
    info["touch"] = dict(left=bool(a[:, :3].max() > 0.5), right=bool(a[:, -3:].max() > 0.5),
                         bottom=bool(a[-3:, :].max() > 0.5))
    return fg, a, info, notes


def place(fg, a, info, S_t, y_crown):
    """Scale to the shared face size and drop onto the 1000x1250 canvas (premultiplied)."""
    import cv2
    k = S_t / info["S"]
    h, w = a.shape
    nw, nh = max(1, round(w * k)), max(1, round(h * k))
    interp = cv2.INTER_AREA if k < 1 else cv2.INTER_LANCZOS4
    pm = np.dstack([fg * a[..., None], a]).astype(np.float32)
    pm = np.clip(cv2.resize(pm, (nw, nh), interpolation=interp), 0, 1)
    ox = round(W / 2 - info["cx"] * k)
    oy = round(y_crown - info["crown"] * k)
    canvas = np.zeros((H, W, 4), np.float32)
    x0, y0 = max(0, ox), max(0, oy)
    x1, y1 = min(W, ox + nw), min(H, oy + nh)
    canvas[y0:y1, x0:x1] = pm[y0 - oy:y1 - oy, x0 - ox:x1 - ox]
    al = canvas[..., 3]
    rgb = np.where(al[..., None] > 1e-4, canvas[..., :3] / np.maximum(al[..., None], 1e-4), 0)
    edges = dict(left=ox, right=ox + nw, top=oy, bottom=oy + nh)
    return grade(np.clip(rgb, 0, 1)), np.clip(al, 0, 1), edges


def save_webp(rgb, a, path):
    arr = np.dstack([rgb, a])
    im = Image.fromarray((arr * 255 + 0.5).astype(np.uint8), "RGBA")
    im.save(path, "WEBP", quality=90, alpha_quality=100, method=6)


def font(size):
    for f in ("/usr/share/fonts/opentype/inter/Inter-Medium.otf",
              "/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf"):
        if os.path.exists(f):
            return ImageFont.truetype(f, size)
    return ImageFont.load_default()


def contact_sheet(paths, names, out):
    tw, th, gap, lab = 320, 400, 10, 30
    rows = [("#F2F3F5", (0xF2, 0xF3, 0xF5)), ("#FFFFFF", (255, 255, 255))]
    sheet = Image.new("RGB", (len(paths) * tw + (len(paths) + 1) * gap, len(rows) * (th + lab) + (len(rows) + 1) * gap),
                      (0xE3, 0xE4, 0xE8))
    d = ImageDraw.Draw(sheet)
    f = font(15)
    for r, (label, col) in enumerate(rows):
        for i, (pth, name) in enumerate(zip(paths, names)):
            im = Image.open(pth).convert("RGBA").resize((tw, th), Image.LANCZOS)
            tile = Image.new("RGBA", (tw, th), col + (255,))
            tile.alpha_composite(im)
            x = gap + i * (tw + gap)
            y = gap + r * (th + lab + gap)
            sheet.paste(tile.convert("RGB"), (x, y))
            d.rectangle((x, y + th, x + tw - 1, y + th + lab - 1), fill=col)
            d.text((x + 10, y + th + 7), f"{name}  on {label}", font=f, fill=(0x3B, 0x41, 0x52))
    os.makedirs(os.path.dirname(out), exist_ok=True)
    sheet.save(out, optimize=True)


def main():
    ap = argparse.ArgumentParser(description=__doc__.split("\n")[0])
    ap.add_argument("--pypath", default="")
    ap.add_argument("--models", default=os.path.expanduser("~/.cache/audazzio-portraits/models"))
    ap.add_argument("--cache", default=os.path.join(tempfile.gettempdir(), "audazzio-portraits"))
    ap.add_argument("--only", default="", help="comma list of slugs to (re)build; framing still uses all six")
    ap.add_argument("--alt", default="", help="comma list of slugs to build from their alt source")
    ap.add_argument("--_matte", nargs=4, metavar=("MODEL", "MODEL_PATH", "IN", "OUT"), help=argparse.SUPPRESS)
    args = ap.parse_args()
    if args._matte:
        matte_worker(*args._matte)
        return

    os.makedirs(args.cache, exist_ok=True)
    os.makedirs(OUT, exist_ok=True)
    alts = set(filter(None, args.alt.split(",")))
    people = []
    for p in PEOPLE:
        p = dict(p)
        if p["slug"] in alts and p.get("alt"):
            p.update(p.pop("alt"))
        p.pop("alt", None)
        people.append(p)

    prepared = []
    for p in people:
        log(f"{p['name']}: {p['src']}")
        fg, a, info, notes = prepare(p, args.models, args.cache)
        log(f"  {'; '.join(notes)}; face scale {info['S']:.0f}px, crown y {info['crown']}, touches {info['touch']}")
        prepared.append((p, fg, a, info))

    # Shared face size: the smallest that still lets every body reach the bottom edge.
    r = [(i["eyes_y"] - i["crown"]) / i["S"] for _, _, _, i in prepared]
    r_med = float(np.median(r))
    need = []
    for (p, _, _, info), ri in zip(prepared, r):
        if info["touch"]["bottom"]:
            b = info["below"] / info["S"]
            need.append((H + BOTTOM_SLACK - CROWN_Y) / (EYE_WEIGHT * (r_med - ri) + b))
    S_t = max(need)
    log(f"shared face scale {S_t:.1f}px (limited by the tightest source)")

    only = set(filter(None, args.only.split(",")))
    paths, names = [], []
    for (p, fg, a, info), ri in zip(prepared, r):
        y_crown = max(MIN_CROWN_Y, CROWN_Y + EYE_WEIGHT * (r_med - ri) * S_t)
        rgb, al, e = place(fg, a, info, S_t, y_crown)
        out = os.path.join(OUT, p["slug"] + ".webp")
        cuts = [s for s in ("left", "right") if info["touch"][s] and 0 < (e[s] if s == "left" else W - e[s])]
        if e["bottom"] < H and info["touch"]["bottom"]:
            cuts.append("bottom")
        log(f"{p['slug']}: head top {y_crown / H:.1%}, eyes {(y_crown + ri * S_t) / H:.1%}"
            + (f", source edge inside frame: {', '.join(cuts)}" if cuts else ""))
        if not only or p["slug"] in only:
            save_webp(rgb, al, out)
            log(f"  wrote {os.path.relpath(out, ROOT)} ({os.path.getsize(out) // 1024} KB)")
        paths.append(out)
        names.append(p["name"])
    contact_sheet(paths, names, SHEET)
    log(f"sheet {os.path.relpath(SHEET, ROOT)}")


if __name__ == "__main__":
    main()
