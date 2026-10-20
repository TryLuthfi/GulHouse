"""Inspect the actual release artifact; this is not a device runtime test."""
import hashlib, json, pathlib, re, subprocess, sys, zipfile
root = pathlib.Path(__file__).resolve().parent.parent
apk = pathlib.Path(sys.argv[1]) if len(sys.argv) > 1 else root / 'releases/android/GulHouse-1.0.0.apk'
checks = []
def check(name, condition):
    if not condition: raise AssertionError(name)
    checks.append({'name': name, 'pass': True})
    print('PASS ' + name)
with zipfile.ZipFile(apk) as archive:
    check('APK ZIP integrity', archive.testzip() is None)
    names = archive.namelist()
    check('ARM64 native libraries packaged', any(n.startswith('lib/arm64-v8a/') and n.endswith('.so') for n in names))
    check('ARMv7 native libraries packaged', any(n.startswith('lib/armeabi-v7a/') and n.endswith('.so') for n in names))
    check('Embedded JavaScript supports standalone release', 'assets/index.android.bundle' in names)
    bundle = archive.read('assets/index.android.bundle')
    check('Online GulHouse API configured', b'https://gulhouse.co.id/master/index.php/api/mobile' in bundle)
    check('Synthetic API URLs not embedded', not any(s in bundle for s in [b'127.0.0.1:61490', b'127.0.0.1:65171', b'127.0.0.1:8033']))
    check('No private runtime/signing/document files packaged', not any(re.search(r'(^|/)(\.env|release\.local\.json|runtime\.json|private_lifecycle|private_documents)(/|$)|\.(jks|keystore)$', n, re.I) for n in names))
sdk = root / '.tools/android-sdk/build-tools'
tools = sorted(sdk.iterdir(), key=lambda p: p.name, reverse=True)[0]
badging = subprocess.check_output([str(tools / 'aapt2.exe'), 'dump', 'badging', str(apk)], text=True, encoding='utf-8')
manifest = subprocess.check_output([str(tools / 'aapt2.exe'), 'dump', 'xmltree', str(apk), '--file', 'AndroidManifest.xml'], text=True, encoding='utf-8')
check('Application package is GulHouse', "package: name='id.gulhouse.app'" in badging)
check('Application version 1.0.0', "versionName='1.0.0'" in badging)
check('Release is not debuggable', 'application-debuggable' not in badging)
def manifest_false(attribute):
    return any(attribute in line and re.search(r'=\s*(?:false|(?:\(type\s+0x12\))?0x0)\s*$', line) for line in manifest.splitlines())
check('Cleartext traffic disabled', manifest_false('usesCleartextTraffic'))
check('Application backup disabled', manifest_false('allowBackup'))
receipt = json.loads((apk.parent / 'build-receipt.json').read_text(encoding='utf-8-sig'))
check('Receipt matches actual APK checksum', receipt['sha256'] == hashlib.sha256(apk.read_bytes()).hexdigest())
check('Private release signing verified', receipt.get('signature_verified') is True and bool(receipt.get('signing_certificate_sha256')))
out = root / '.e2e/mobile/apk-inspection.json'
out.write_text(json.dumps({'scope':'APK artifact only; device runtime unverified','checks':checks}, indent=2), encoding='utf-8')
