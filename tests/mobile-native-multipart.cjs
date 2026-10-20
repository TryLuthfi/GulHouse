/* Independent Expo 58 multipart regression. No HTTP, DB, device or app writes.
 * Executes installed Expo converter + FormData patch and real app apiUpload.
 * NativeFile JSI is unavailable in Node: only this boundary uses a disk adapter.
 */
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const { pathToFileURL, fileURLToPath } = require('node:url');
const root = path.resolve(__dirname, '..');
const ts = require(path.join(root, 'mobile/node_modules/typescript'));
const results = [];
function check(name, fn) { fn(); results.push({ name, pass: true }); }
function load(relative, deps, globals = {}) {
  const filename = path.join(root, relative);
  const source = fs.readFileSync(filename, 'utf8');
  const js = ts.transpileModule(source, { compilerOptions: { module: ts.ModuleKind.CommonJS, target: ts.ScriptTarget.ES2022 } }).outputText;
  const exports = {};
  const context = { exports, module: { exports }, require: (key) => {
    if (!(key in deps)) throw new Error('Unexpected dependency: ' + key);
    return deps[key];
  }, Blob, TextEncoder, TextDecoder, URL, URLSearchParams, Uint8Array, AbortController, setTimeout, clearTimeout, process: { env: {} }, ...globals };
  vm.runInNewContext(js, context, { filename });
  return exports;
}
(async () => {
  // RN storage surface, then the exact installed Expo patch owns normalization/iteration.
  class RNFormDataSeed { constructor() { this._parts = []; } }
  const patch = load('mobile/node_modules/expo/src/winter/FormData.ts', {});
  const ExpoFormData = patch.installFormDataPatch(RNFormDataSeed);
  const converter = load('mobile/node_modules/expo/src/winter/fetch/convertFormData.ts', {
    '../../utils/blobUtils': { blobToArrayBufferAsync: (blob) => blob.arrayBuffer() },
  });
  const legacy = new ExpoFormData();
  legacy.append('ktp', { uri: 'file:///never-read.png', name: 'old.png', type: 'image/png' });
  await assert.rejects(converter.convertFormDataAsync(legacy), /Unsupported FormDataPart implementation/);
  results.push({ name: 'Installed Expo rejects old RN URI multipart shape', pass: true });
  const files = [
    { key: 'ktp', disk: '.e2e/mobile/online/qa-ktp.png', name: 'KTP terbaru é.png', mimeType: 'image/png' },
    { key: 'scan_file', disk: '.e2e/lifecycle/zeyn/print-routine.pdf', name: 'folder/checklist.pdf', mimeType: 'application/pdf' },
  ];
  for (const file of files) assert(fs.statSync(path.join(root, file.disk)).size > 100);
  let nativeReads = 0, captured, capturedHeaders;
  class NativeFileDiskAdapter {
    constructor(uri) { this.uri = uri; }
    async bytes() { nativeReads++; return new Uint8Array(await fs.promises.readFile(fileURLToPath(this.uri))); }
  }
  const api = load('mobile/src/api/client.ts', {
    'react-native': { Platform: { OS: 'android' } },
    'expo-secure-store': {}, 'expo-file-system/legacy': {},
    'expo-file-system': { File: NativeFileDiskAdapter }, 'expo-sharing': {},
  }, { FormData: ExpoFormData, fetch: async (url, init) => {
    assert.match(url, /^https:\/\/gulhouse\.co\.id\/master\/index\.php\/api\/mobile\/resident\/intake$/);
    capturedHeaders = init.headers;
    captured = await converter.convertFormDataAsync(init.body, 'CIKO-Expo58-Boundary');
    return { ok: true, status: 200, text: async () => JSON.stringify({ ok: true, data: { synthetic: true } }) };
  }});
  const payload = { fullname: 'CIKO Synthetic', consent: true, optional: false, items: [{ code: 'AC', qty: 1 }, { code: 'Bantal', qty: 2 }], absent: null };
  const picked = Object.fromEntries(files.map((f) => [f.key, { uri: pathToFileURL(path.join(root, f.disk)).href, name: f.name, mimeType: f.mimeType }]));
  const response = await api.apiUpload('resident/intake', payload, picked);
  check('Actual app apiUpload traverses native byte-backed path', () => { assert.equal(response.synthetic, true); assert.equal(nativeReads, 2); });
  check('Transport leaves multipart Content-Type boundary to Expo fetch', () => assert.equal(capturedHeaders['Content-Type'], undefined));
  const parsed = await new Response(captured.body, { headers: { 'Content-Type': `multipart/form-data; boundary=${captured.boundary}` } }).formData();
  check('Original nested JSON payload preserved', () => assert.deepEqual(JSON.parse(parsed.get('payload')), payload));
  check('Nested fields and boolean values preserved', () => {
    assert.equal(parsed.get('items[1][code]'), 'Bantal'); assert.equal(parsed.get('items[1][qty]'), '2');
    assert.equal(parsed.get('consent'), '1'); assert.equal(parsed.get('optional'), '0'); assert.equal(parsed.has('absent'), false);
  });
  for (const file of files) {
    const part = parsed.get(file.key);
    check(file.key + ' filename and MIME emitted by installed converter', () => {
      assert.equal(part.name, encodeURIComponent(file.name.replace(/\//g, '_'))); assert.equal(part.type, file.mimeType);
    });
    const actual = Buffer.from(await part.arrayBuffer());
    check(file.key + ' full binary bytes preserved including non-text bytes', () => assert.deepEqual(actual, fs.readFileSync(path.join(root, file.disk))));
  }
  const bad = new ExpoFormData();
  bad.append('broken', { name: 'fail.png', type: 'image/png', bytes: async () => { throw new Error('Synthetic disk read failure'); } });
  await assert.rejects(converter.convertFormDataAsync(bad), /Synthetic disk read failure/);
  results.push({ name: 'Unreadable bytes reject rather than empty successful upload', pass: true });
  const output = { scope: 'Node regression against installed Expo converter/FormData patch + actual apiUpload; NativeFile disk adapter only; not physical Android success', pass: results.every((r) => r.pass), count: results.length, results };
  const out = path.join(root, '.e2e/mobile/online/ciko/native-multipart-results.json');
  fs.mkdirSync(path.dirname(out), { recursive: true }); fs.writeFileSync(out, JSON.stringify(output, null, 2));
  console.log(JSON.stringify({ pass: output.pass, count: output.count, output: path.relative(root, out), limitation: output.scope }));
})().catch((error) => { console.error(error.stack); process.exitCode = 1; });
