param([string]$Php='D:\XAMPP\php\php.exe')
$ErrorActionPreference='Stop'
$savedBillingTestDatabase=$env:GULHOUSE_TEST_DATABASE
$savedBillingDatabase=$env:GULHOUSE_DATABASE
$env:GULHOUSE_TEST_DATABASE='gulhouse_test_billing_'+[DateTime]::UtcNow.ToString('yyyyMMdd_HHmmss_fff')
try {
    $billingFixture = & $Php (Join-Path $PSScriptRoot 'setup-gulhouse-test.php')
    if ($LASTEXITCODE -ne 0) { throw 'Synthetic billing fixture setup failed.' }
    $billingSetup=$billingFixture|ConvertFrom-Json
    $env:GULHOUSE_DATABASE=$billingSetup.database
    & $Php (Join-Path $PSScriptRoot '../tests/verify-billing-job.php')
    $billingTestCode=$LASTEXITCODE
    Write-Host ('Retained only synthetic billing schema: '+$billingSetup.database)
    exit $billingTestCode
} finally {
    $env:GULHOUSE_TEST_DATABASE=$savedBillingTestDatabase
    $env:GULHOUSE_DATABASE=$savedBillingDatabase
}
