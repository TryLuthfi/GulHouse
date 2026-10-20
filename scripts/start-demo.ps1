$ErrorActionPreference='Stop'
$repoRoot=(Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$php='D:\XAMPP\php\php.exe'
$env:GULHOUSE_TEST_DATABASE='gulhouse_test_demo_' + (Get-Date -Format 'yyyyMMdd_HHmmss_fff')
$env:GULHOUSE_E2E_USER='codex_trial'
$env:GULHOUSE_E2E_PASS='LocalDemo2026!'
$fixture=& $php (Join-Path $PSScriptRoot 'setup-gulhouse-test.php') --demo
if($LASTEXITCODE -ne 0){throw 'Demo fixture creation failed.'}
$setup=$fixture|ConvertFrom-Json
$env:GULHOUSE_DATABASE=$setup.database
$env:GULHOUSE_REPORT_PERIOD='2026-11'
Write-Host 'Demo synthetic only. Real operational data is separate.'
Write-Host 'Landing: http://127.0.0.1:8034/landing/'
Write-Host 'Master: http://127.0.0.1:8034/master/login'
Write-Host 'Demo login: codex_trial / LocalDemo2026!'
Write-Host 'Resident demo: 089991000001 / last 4 NIK 1001'
Write-Host "Schema: $($setup.database). Keep this window running during the demo."
Push-Location $repoRoot
try { & $php -S 127.0.0.1:8034 -t . scripts/dev-router.php } finally { Pop-Location }
