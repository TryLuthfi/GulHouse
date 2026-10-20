// Loopback-only preview of the Expo export. No directory listings or private files.
const http = require("node:http"),
  fs = require("node:fs"),
  path = require("node:path");
const directory = process.env.GULHOUSE_PREVIEW_DIR || "dist";
if (!["dist", "dist-next"].includes(directory)) throw Error("Only public Expo export directories may be previewed.");
const root = fs.realpathSync(path.resolve(__dirname, "../mobile/" + directory));
const port = Number(process.env.GULHOUSE_PREVIEW_PORT || 50115);
const mime = {
  ".html": "text/html",
  ".js": "application/javascript",
  ".json": "application/json",
  ".png": "image/png",
  ".jpg": "image/jpeg",
  ".webp": "image/webp",
  ".ttf": "font/ttf",
  ".woff": "font/woff",
  ".woff2": "font/woff2",
  ".ico": "image/x-icon",
};
http
  .createServer((req, res) => {
    if (!["GET", "HEAD"].includes(req.method)) {
      res.writeHead(405).end();
      return;
    }
    let requested;
    try {
      requested = decodeURIComponent(
        new URL(req.url, "http://127.0.0.1").pathname,
      );
    } catch {
      res.writeHead(400).end();
      return;
    }
    if (
      requested
        .split("/")
        .some((part) => part.startsWith(".") || part.includes("\\"))
    ) {
      res.writeHead(403).end();
      return;
    }
    const file = path.resolve(
      root,
      "." + (requested === "/" ? "/index.html" : requested),
    );
    if (!file.startsWith(root + path.sep) || !mime[path.extname(file)]) {
      res.writeHead(404).end();
      return;
    }
    let actual;
    try {
      actual = fs.realpathSync(file);
      if (!actual.startsWith(root + path.sep) || !fs.statSync(actual).isFile())
        throw Error();
    } catch {
      res.writeHead(404).end();
      return;
    }
    res.writeHead(200, {
      "Content-Type": mime[path.extname(file)],
      "Cache-Control": "no-store",
      "X-Content-Type-Options": "nosniff",
    });
    if (req.method === "HEAD") res.end();
    else fs.createReadStream(actual).pipe(res);
  })
  .listen(port, "127.0.0.1", () =>
    console.log("Native Expo preview on loopback port " + port),
  );
