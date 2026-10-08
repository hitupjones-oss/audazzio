// YouTube will not play in a headless browser (its bot check answers every clip with "content isn't
// available"), so the checks and the screenshots stand in a small copy of the iframe API: the same events in
// the same order (-1, 3, 1), pause, play and destroy, and a still of the clip where the video would be.
import fs from "node:fs";
import path from "node:path";

const ROOT = path.join(import.meta.dirname, "..", "..");
const STILLS = [path.join(ROOT, "media", "src", "yt"), path.join(ROOT, "wp-content", "plugins", "audazzio-core", "assets", "img")];

const API = `(function(){
  function Player(host, o) {
    var self = this, f = document.createElement('div'), ev = o.events || {};
    f.style.cssText = 'position:absolute;inset:0;background:#0c1222 url(https://yt.stub/' + o.videoId + '.jpg) center/cover no-repeat';
    host.replaceWith(f);
    self.f = f; self.st = -1; self.dead = false;
    self.emit = function (s) { if (self.dead) return; self.st = s; ev.onStateChange && ev.onStateChange({ data: s, target: self }); };
    setTimeout(function () { if (self.dead) return; ev.onReady && ev.onReady({ target: self });
      self.emit(-1); setTimeout(function () { self.emit(3); setTimeout(function () { self.emit(1); }, 250); }, 150); }, 150);
  }
  Player.prototype.pauseVideo = function () { this.emit(2); };
  Player.prototype.playVideo = function () { var s = this; s.emit(3); setTimeout(function () { if (s.st === 3) s.emit(1); }, 150); };
  Player.prototype.getPlayerState = function () { return this.st; };
  Player.prototype.destroy = function () { this.dead = true; this.f.remove(); };
  window.YT = { Player: Player };
  setTimeout(function () { window.onYouTubeIframeAPIReady && window.onYouTubeIframeAPIReady(); }, 0);
})();`;

/** Route a page (or a whole browser context) to the stand-in. */
export async function stubYouTube(target) {
  await target.route("https://www.youtube.com/iframe_api*", (r) => r.fulfill({ contentType: "text/javascript", body: API }));
  await target.route("https://yt.stub/*", (r) => {
    const id = new URL(r.request().url()).pathname.slice(1).replace(/\.jpg$/, "");
    const file = [path.join(STILLS[0], `${id}-maxresdefault.jpg`), path.join(STILLS[1], `poster-${id}.jpg`), path.join(STILLS[1], "poster-demo.jpg")].find((f) => fs.existsSync(f));
    return file ? r.fulfill({ contentType: "image/jpeg", body: fs.readFileSync(file) }) : r.fulfill({ status: 404, body: "" });
  });
}
