param([string]$ApiUrl='https://gulhouse.co.id/master/index.php/api/mobile',[string]$Architectures='arm64-v8a,armeabi-v7a',[switch]$SkipPrebuild)
$ErrorActionPreference='Stop'
$repo=(Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$mobile=Join-Path $repo 'mobile'
$java=Get-ChildItem (Join-Path $repo '.tools/jdk') -Directory | Where-Object Name -Like 'jdk-*' | Select-Object -First 1
if(!$java){throw 'Install a JDK 21 under .tools/jdk, or adapt the build environment before running.'}
$sdk=Join-Path $repo '.tools/android-sdk'
if(!(Test-Path $sdk)){throw 'Install Android SDK under .tools/android-sdk first.'}
$private=Join-Path $repo '.deploy/android-signing'
New-Item -ItemType Directory -Force $private | Out-Null
if($IsWindows){
    $current=Get-Acl -LiteralPath $private
    $identities=@([Security.Principal.WindowsIdentity]::GetCurrent().User,[Security.Principal.SecurityIdentifier]::new('S-1-5-18'))
    $allowed=$identities | ForEach-Object {$_.Value}
    $unexpected=@($current.Access | Where-Object {$_.AccessControlType -eq 'Allow' -and $_.IdentityReference.Translate([Security.Principal.SecurityIdentifier]).Value -notin $allowed})
    if(!$current.AreAccessRulesProtected -or $unexpected.Count){
    # Write only the DACL; replaying a fetched descriptor can request SACL privileges.
    $acl=[Security.AccessControl.DirectorySecurity]::new()
    $acl.SetAccessRuleProtection($true,$false)
    $inherit=[Security.AccessControl.InheritanceFlags]'ContainerInherit,ObjectInherit'
    foreach($sid in $identities){
        $acl.AddAccessRule([Security.AccessControl.FileSystemAccessRule]::new($sid,'FullControl',$inherit,[Security.AccessControl.PropagationFlags]::None,[Security.AccessControl.AccessControlType]::Allow))
    }
    [System.IO.FileSystemAclExtensions]::SetAccessControl([System.IO.DirectoryInfo]::new($private),$acl)
    }
}
$secretPath=Join-Path $private 'release.local.json'
$store=Join-Path $private 'gulhouse-release.jks'
$names=@('JAVA_HOME','ANDROID_HOME','ANDROID_SDK_ROOT','GRADLE_USER_HOME','EXPO_PUBLIC_API_URL','GULHOUSE_ANDROID_KEYSTORE','GULHOUSE_ANDROID_KEYSTORE_PASSWORD','PATH','CI')
$saved=@{};foreach($name in $names){$saved[$name]=[Environment]::GetEnvironmentVariable($name,'Process')}
function SourceSignature {
    $inputs=@(Get-ChildItem (Join-Path $mobile 'src'),(Join-Path $mobile 'plugins'),(Join-Path $mobile 'assets') -File -Recurse)
    foreach($name in @('App.tsx','index.ts','app.json','package.json','package-lock.json')){$inputs+=Get-Item (Join-Path $mobile $name)}
    $manifest=($inputs | Sort-Object FullName | ForEach-Object {$_.FullName.Substring($mobile.Length)+':'+(Get-FileHash -LiteralPath $_.FullName -Algorithm SHA256).Hash}) -join "`n"
    return [Convert]::ToHexString([Security.Cryptography.SHA256]::HashData([Text.Encoding]::UTF8.GetBytes($manifest))).ToLower()
}
function NativeSignature {
    $inputs=@(Get-ChildItem (Join-Path $mobile 'plugins'),(Join-Path $mobile 'assets') -File -Recurse)
    foreach($name in @('app.json','package.json','package-lock.json')){$inputs+=Get-Item (Join-Path $mobile $name)}
    $manifest=($inputs | Sort-Object FullName | ForEach-Object {$_.FullName.Substring($mobile.Length)+':'+(Get-FileHash -LiteralPath $_.FullName -Algorithm SHA256).Hash}) -join "`n"
    return [Convert]::ToHexString([Security.Cryptography.SHA256]::HashData([Text.Encoding]::UTF8.GetBytes($manifest))).ToLower()
}
try {
    $env:JAVA_HOME=$java.FullName;$env:ANDROID_HOME=$sdk;$env:ANDROID_SDK_ROOT=$sdk
    $env:GRADLE_USER_HOME=Join-Path $repo '.tools/gradle'
    $env:EXPO_PUBLIC_API_URL=$ApiUrl;$env:CI='1'
    $env:PATH=(Join-Path $repo '.tools/bin')+';'+(Join-Path $mobile 'node_modules/.bin')+';'+$env:PATH
    if(!(Test-Path $secretPath)){
        if(Test-Path $store){throw 'Signing key exists without its secret receipt; recover the original secret rather than replace the key.'}
        $password=[Convert]::ToBase64String([Security.Cryptography.RandomNumberGenerator]::GetBytes(36))
        @{password=$password;alias='gulhouse'} | ConvertTo-Json | Set-Content -Encoding utf8 $secretPath
    }
    $secret=Get-Content -Raw $secretPath | ConvertFrom-Json
    $env:GULHOUSE_ANDROID_KEYSTORE_PASSWORD=$secret.password;$env:GULHOUSE_ANDROID_KEYSTORE=$store
    if(!(Test-Path $store)){
        & (Join-Path $java.FullName 'bin/keytool.exe') -genkeypair -keystore $store -alias gulhouse -keyalg RSA -keysize 3072 -validity 10000 -storetype JKS -storepass:env GULHOUSE_ANDROID_KEYSTORE_PASSWORD -keypass:env GULHOUSE_ANDROID_KEYSTORE_PASSWORD -dname 'CN=GulHouse, OU=Mobile, O=GulHouse, C=ID' 2> (Join-Path $private 'key-generation.log')
        if($LASTEXITCODE -ne 0){throw 'Signing key generation failed; inspect private log.'}
    }
    Push-Location $mobile
    try {
        $stamp=Join-Path $repo '.e2e/mobile/native-project-config.sha256'
        if($SkipPrebuild){
            if(!(Test-Path (Join-Path $mobile 'android/app/build.gradle')) -or !(Test-Path $stamp) -or (Get-Content -Raw $stamp).Trim() -ne (NativeSignature)){throw 'Native configuration changed or no verified prebuild exists. Run without -SkipPrebuild.'}
        } else {
        & node (Join-Path $mobile 'node_modules/expo/bin/cli') prebuild --platform android --no-install
        if($LASTEXITCODE -ne 0){throw 'Expo prebuild failed.'}
        New-Item -ItemType Directory -Force (Split-Path $stamp) | Out-Null
        NativeSignature | Set-Content $stamp
        }
        $sourceBefore=SourceSignature
        Push-Location (Join-Path $mobile 'android')
        try {
            & ./gradlew.bat :app:assembleRelease ('-PreactNativeArchitectures='+$Architectures) --no-daemon --max-workers=2
            if($LASTEXITCODE -ne 0){throw 'Android release build failed.'}
        } finally {Pop-Location}
        if((SourceSignature) -ne $sourceBefore){throw 'Native source changed during the build. Freeze source and rerun; this APK will not be published.'}
        $buildTools=Get-ChildItem (Join-Path $sdk 'build-tools') -Directory | Sort-Object Name -Descending | Select-Object -First 1
        $apkSource=Join-Path $mobile 'android/app/build/outputs/apk/release/app-release.apk'
        $signature=& (Join-Path $buildTools.FullName 'apksigner.bat') verify --verbose --print-certs $apkSource
        if($LASTEXITCODE -ne 0){throw 'APK signature verification failed.'}
        $publicCertificate=Join-Path $private 'gulhouse-public.der'
        & (Join-Path $java.FullName 'bin/keytool.exe') -exportcert -keystore $store -alias gulhouse -storepass:env GULHOUSE_ANDROID_KEYSTORE_PASSWORD -file $publicCertificate 2> (Join-Path $private 'certificate-export.log')
        if($LASTEXITCODE -ne 0){throw 'Signing certificate export failed.'}
        $certificateHash=(Get-FileHash -LiteralPath $publicCertificate -Algorithm SHA256).Hash.ToLower()
        if(($signature -join "`n") -notmatch ('certificate SHA-256 digest: '+$certificateHash)){throw 'APK was not signed by the GulHouse private release key.'}
        Write-Output 'APK signature verified against the private release certificate.'
        $out=Join-Path $repo 'releases/android'
        New-Item -ItemType Directory -Force $out | Out-Null
        $apk=Join-Path $out 'GulHouse-1.0.0.apk'
        Copy-Item -LiteralPath $apkSource -Destination $apk
        $hash=(Get-FileHash -LiteralPath $apk -Algorithm SHA256).Hash.ToLower()
        @{file='GulHouse-1.0.0.apk';sha256=$hash;source_sha256=$sourceBefore;signature_verified=$true;signing_certificate_sha256=$certificateHash;api=$ApiUrl;architectures=$Architectures;built_at=(Get-Date -Format o)} | ConvertTo-Json | Set-Content (Join-Path $out 'build-receipt.json')
        Write-Output ('APK: '+$apk+'; SHA256: '+$hash)
    } finally {Pop-Location}
} finally {foreach($name in $names){[Environment]::SetEnvironmentVariable($name,$saved[$name],'Process')}}
