$ErrorActionPreference = 'Stop'
$repoRoot = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$php = 'D:\XAMPP\php\php.exe'
$env:GULHOUSE_TEST_DATABASE = 'gulhouse_test_acceptance_' + (Get-Date -Format 'yyyyMMdd_HHmmss_fff')
$env:GULHOUSE_E2E_USER = 'codex_trial'
$env:GULHOUSE_E2E_PASS = [guid]::NewGuid().ToString('N')
$savedDatabase = $env:GULHOUSE_DATABASE
$fixture = & $php (Join-Path $PSScriptRoot 'setup-gulhouse-test.php')
if ($LASTEXITCODE -ne 0) { throw 'Isolated fixture creation failed.' }
$setup = $fixture | ConvertFrom-Json
$env:GULHOUSE_DATABASE = $setup.database
$env:GULHOUSE_TEST_URL = 'http://127.0.0.1:8033'
$logDir = Join-Path $repoRoot '.e2e\acceptance'
New-Item -ItemType Directory -Force -Path $logDir | Out-Null
$stdout = Join-Path $logDir 'server.log'
$stderr = Join-Path $logDir 'server-errors.log'
$server = Start-Process -FilePath $php -ArgumentList @('-S','127.0.0.1:8033','-t','.', 'scripts/dev-router.php') -WorkingDirectory $repoRoot -WindowStyle Hidden -RedirectStandardOutput $stdout -RedirectStandardError $stderr -PassThru
$runCode = 0
Push-Location $repoRoot
try {
    $ready = $false
    for ($attempt=0; $attempt -lt 20; $attempt++) {
        try { $response=Invoke-WebRequest -Uri ($env:GULHOUSE_TEST_URL+'/master/index.php/login') -TimeoutSec 2; $ready=$response.StatusCode -eq 200 } catch {}
        if ($ready) { break }
        Start-Sleep -Milliseconds 250
    }
    if (!$ready) { throw 'Isolated server did not become ready.' }
    foreach ($test in @('verify-flow.php','verify-operations.php','verify-access.php','verify-upload.php','verify-deposit-policy.php','verify-payment-proofs.php','verify-csv-export.php')) {
        & $php (Join-Path 'tests' $test) | Tee-Object -FilePath (Join-Path $logDir ($test+'.log'))
        if ($LASTEXITCODE -ne 0) { $runCode=$LASTEXITCODE; throw "Acceptance failed: $test" }
    }
    Write-Host "All HTTP/database acceptance checks passed. Synthetic schema retained: $($setup.database)"
} finally {
    Pop-Location
    if (!$server.HasExited) { Stop-Process -Id $server.Id }
    $env:GULHOUSE_DATABASE = $savedDatabase
}
exit $runCode
