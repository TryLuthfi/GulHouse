$ErrorActionPreference = 'Stop'
$repoRoot = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$nodeDir = Join-Path $repoRoot '.tools\node-v24.21.0-win-x64'
$npm = Join-Path $nodeDir 'npm.cmd'
$php = if ($env:PHP_BIN) { $env:PHP_BIN } else { 'D:\XAMPP\php\php.exe' }
if (!(Test-Path -LiteralPath $npm)) { throw 'Portable Node runtime is missing.' }
if (!(Test-Path -LiteralPath $php)) { throw 'PHP runtime is missing.' }
if ($env:APP_URL) { throw 'Unset APP_URL: tests must launch their own isolated application.' }
$savedDatabase = $env:GULHOUSE_DATABASE
$env:GULHOUSE_TEST_DATABASE = 'gulhouse_test_' + (Get-Date -Format 'yyyyMMdd_HHmmss_fff')
$env:GULHOUSE_E2E_USER = 'codex_trial'
if (!$env:GULHOUSE_E2E_PASS) { $env:GULHOUSE_E2E_PASS = [guid]::NewGuid().ToString('N') }
$fixtureArgs = @()
if ($env:GULHOUSE_E2E_DEMO -eq '1') { $fixtureArgs += '--demo' }
$fixture = & $php (Join-Path $PSScriptRoot 'setup-gulhouse-test.php') @fixtureArgs
if ($LASTEXITCODE -ne 0) { throw 'Could not create an isolated test database.' }
$setup = $fixture | ConvertFrom-Json
$env:GULHOUSE_DATABASE = $setup.database
$env:GULHOUSE_E2E_ROOM_ID = [string]$setup.room_id
$env:GULHOUSE_E2E_ROOM_ID_WIDE = [string]$setup.room_id_wide
$env:GULHOUSE_E2E_ROOM_ID_MOBILE = [string]$setup.room_id_mobile
$env:GULHOUSE_E2E_ROOM_ID_TENANT = [string]$setup.room_id_tenant
$env:GULHOUSE_E2E_ROOM_ID_TENANT_WIDE = [string]$setup.room_id_tenant_wide
$env:GULHOUSE_E2E_ROOM_ID_TENANT_MOBILE = [string]$setup.room_id_tenant_mobile
$env:GULHOUSE_E2E_ROOM_ID_JOURNEY = [string]$setup.room_id_journey
$env:GULHOUSE_E2E_ROOM_ID_JOURNEY_WIDE = [string]$setup.room_id_journey_wide
$env:GULHOUSE_E2E_ROOM_ID_JOURNEY_MOBILE = [string]$setup.room_id_journey_mobile
$env:GULHOUSE_E2E_ROOM_ID_FINANCIAL = [string]$setup.room_id_financial
$env:GULHOUSE_E2E_ROOM_ID_FINANCIAL_WIDE = [string]$setup.room_id_financial_wide
$env:GULHOUSE_E2E_ROOM_ID_FINANCIAL_MOBILE = [string]$setup.room_id_financial_mobile
$env:GULHOUSE_E2E_ROOM_ID_BILLING = [string]$setup.room_id_billing
$env:GULHOUSE_E2E_ROOM_ID_BILLING_WIDE = [string]$setup.room_id_billing_wide
$env:GULHOUSE_E2E_ROOM_ID_BILLING_MOBILE = [string]$setup.room_id_billing_mobile
$env:GULHOUSE_E2E_OUTPUT = '.e2e'
for ($index=0; $index -lt $args.Count; $index++) {
    if ($args[$index] -eq '--output' -and $index+1 -lt $args.Count) { $env:GULHOUSE_E2E_OUTPUT = [string]$args[$index+1] }
}
$env:PATH = "$nodeDir;$env:PATH"
$env:E2E_TELEMETRY_DISABLED = '1'
$env:PHP_BIN = $php
$npmScript = if ($env:GULHOUSE_E2E_SCRIPT) { $env:GULHOUSE_E2E_SCRIPT } else { 'e2e:full' }
Write-Host "Testing isolated schema $($setup.database); no real occupants copied."
$runCode = 1
Push-Location $repoRoot
try {
    & $npm run $npmScript -- @args
    $runCode = $LASTEXITCODE
} finally {
    Pop-Location
    $env:GULHOUSE_DATABASE = $savedDatabase
}
Write-Host "Synthetic test database retained for inspection: $($setup.database)"
exit $runCode
