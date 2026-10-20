param([switch]$KeepServer)
$ErrorActionPreference='Stop'
$repoRoot=(Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$php='D:/XAMPP/php/php.exe'
$python=if($env:GULHOUSE_OCR_PYTHON){$env:GULHOUSE_OCR_PYTHON}else{'C:/Users/TKM/.cache/codex-runtimes/codex-primary-runtime/dependencies/python/python.exe'}
$node=if(Test-Path 'C:/Users/TKM/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/bin/node.exe'){'C:/Users/TKM/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/bin/node.exe'}else{'node'}
$names=@('GULHOUSE_DATABASE','GULHOUSE_TEST_DATABASE','GULHOUSE_E2E_USER','GULHOUSE_E2E_PASS','GULHOUSE_OCR_PYTHON')
$saved=@{};foreach($name in $names){$saved[$name]=[Environment]::GetEnvironmentVariable($name,'Process')}
$server=$null
Push-Location $repoRoot
try {
    New-Item -ItemType Directory -Force '.e2e/lifecycle' | Out-Null
    & $python -c "import sys;sys.path.insert(0,'.tools/ocr-python');import rapidocr_onnxruntime,pypdfium2"
    if($LASTEXITCODE -ne 0){throw 'Install the local OCR dependencies from scripts/lifecycle-ocr-requirements.txt into .tools/ocr-python first.'}
    & $python tests/lifecycle-ocr-fixtures.py
    if($LASTEXITCODE -ne 0){throw 'Synthetic document setup failed.'}
    $env:GULHOUSE_TEST_DATABASE='gulhouse_test_lifecycle_'+(Get-Date -Format 'yyyyMMdd_HHmmss_fff')
    $env:GULHOUSE_E2E_USER='codex_trial';$env:GULHOUSE_E2E_PASS=[guid]::NewGuid().ToString('N')
    $fixtureRaw=& $php scripts/setup-gulhouse-test.php --demo
    if($LASTEXITCODE -ne 0){throw 'Synthetic database setup failed.'}
    $fixture=$fixtureRaw | ConvertFrom-Json
    $env:GULHOUSE_DATABASE=$fixture.database;$env:GULHOUSE_OCR_PYTHON=$python
    $listener=[System.Net.Sockets.TcpListener]::new([System.Net.IPAddress]::Loopback,0)
    $listener.Start();$port=$listener.LocalEndpoint.Port;$listener.Stop()
    $url='http://127.0.0.1:'+$port
    $server=Start-Process -FilePath $php -ArgumentList @('-S',('127.0.0.1:'+$port),'-t','.', 'scripts/dev-router.php') -WorkingDirectory $repoRoot -WindowStyle Hidden -RedirectStandardOutput '.e2e/lifecycle/server.log' -RedirectStandardError '.e2e/lifecycle/server-errors.log' -PassThru
    @{schema=$fixture.database;url=$url;username=$env:GULHOUSE_E2E_USER;password=$env:GULHOUSE_E2E_PASS;server_pid=$server.Id;fixture=$fixture} | ConvertTo-Json -Depth 4 | Set-Content -Encoding utf8 '.e2e/lifecycle/runtime.json'
    $ready=$false
    for($attempt=0;$attempt -lt 25;$attempt++){
        try{$ready=(Invoke-WebRequest ($url+'/master/index.php/login') -TimeoutSec 2).StatusCode -eq 200}catch{}
        if($ready){break};Start-Sleep -Milliseconds 200
    }
    if(!$ready){throw 'Synthetic application server did not become ready.'}
    Write-Host ('Running actual Playwright on isolated schema '+$fixture.database)
    & $node tests/lifecycle-playwright.cjs
    if($LASTEXITCODE -ne 0){throw 'Lifecycle Playwright failed. See .e2e/lifecycle/zeyn/results.json.'}
    Write-Host 'Lifecycle Playwright PASS; synthetic schema retained for inspection.'
} finally {
    if($server -and !$KeepServer -and !$server.HasExited){Stop-Process -Id $server.Id}
    foreach($name in $names){[Environment]::SetEnvironmentVariable($name,$saved[$name],'Process')}
    Pop-Location
}
