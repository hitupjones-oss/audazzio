"""Make the case-study PDFs light enough for the web (and for a plugin zip that uploads on any host).
Every picture inside is re-saved as a JPEG no wider than 1800 px; text and vectors are untouched.
    python3 scripts/media/shrink_pdf.py in.pdf out.pdf     (needs pikepdf and Pillow; AZ_PYPATH may name
    extra folders to import them from)
"""
import io, os, sys
sys.path[:0] = [p for p in os.environ.get("AZ_PYPATH", "").split(":") if p]
import pikepdf
from pikepdf import PdfImage, Name
from PIL import Image

src, dst = sys.argv[1], sys.argv[2]
pdf = pikepdf.open(src)
done = 0
for page in pdf.pages:
    for name, raw in list(page.images.items()):  # noqa: page.images is deprecated in newer pikepdf
        try:
            img = PdfImage(raw)
            if raw.get("/SMask") is not None or img.bits_per_component != 8:
                continue
            pil = img.as_pil_image()
            if pil.mode not in ("RGB", "L"):
                pil = pil.convert("RGB")
            if pil.width > 1800:
                pil = pil.resize((1800, round(pil.height * 1800 / pil.width)), Image.LANCZOS)
            buf = io.BytesIO()
            pil.save(buf, "JPEG", quality=78, optimize=True, progressive=True)
            if buf.tell() >= len(raw.read_raw_bytes()):
                continue
            raw.write(buf.getvalue(), filter=Name.DCTDecode)
            raw.Width, raw.Height = pil.width, pil.height
            raw.ColorSpace = Name.DeviceRGB if pil.mode == "RGB" else Name.DeviceGray
            raw.BitsPerComponent = 8
            for k in ("/DecodeParms", "/Decode"):
                if k in raw:
                    del raw[k]
            done += 1
        except Exception as e:  # an image pikepdf cannot decode stays as it is
            print("kept", name, e)
pdf.save(dst, compress_streams=True, object_stream_mode=pikepdf.ObjectStreamMode.generate)
print(f"{src}: {done} images re-saved")
