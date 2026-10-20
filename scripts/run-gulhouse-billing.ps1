param(
    [Parameter(Mandatory=$true)][ValidatePattern('^(?:gulhouse_test_[A-Za-z0-9_]+|gulhouse_db)$')][string]$Database,
    [ValidatePattern('^\d{4}-\d{2}$')][string]$Period,
    [switch]$Apply,
    [switch]$Automatic,
    [ValidateRange(2,31)][int]$LeadDays = 7,
    [switch]$AllowRealDatabase,
    [string]$Php = 'D:\XAMPP\php\php.exe',
    [string]$LogPath
)
$ErrorActionPreference = 'Stop'
if ($Database -eq 'gulhouse_db' -and !$AllowRealDatabase) { throw 'Real database requires explicit -AllowRealDatabase. Preview is otherwise synthetic-only.' }
if (!(Test-Path -LiteralPath $Php)) { throw 'PHP executable not found.' }
$billingRoot = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$savedBillingDatabase = $env:GULHOUSE_DATABASE
$env:GULHOUSE_DATABASE = $Database
$billingArgs = @((Join-Path $PSScriptRoot 'run-gulhouse-billing.php'))
if ($Period) { $billingArgs += '--period=' + $Period }
if ($Automatic) { $billingArgs += '--automatic'; $billingArgs += '--lead-days=' + $LeadDays }
if ($Apply) { $billingArgs += '--apply' }
if ($AllowRealDatabase) { $billingArgs += '--allow-real-database' }
try {
    Push-Location $billingRoot
    try { $billingOutput = & $Php @billingArgs; $billingCode = $LASTEXITCODE } finally { Pop-Location }
    try {
        $billingResult = ($billingOutput -join "`n") | ConvertFrom-Json -ErrorAction Stop
        if ($null -eq $billingResult -or $null -eq $billingResult.ok) { throw 'Missing billing result.' }
        if (!$billingResult.ok -and $billingCode -eq 0) { $billingCode = 1 }
    } catch {
        $billingCode = 1
        $billingOutput = '{"ok":false,"error":"Billing runner returned no valid result; inspect the private application log."}'
    }
    if ($LogPath) {
        $billingLogDirectory = Split-Path -Parent ([IO.Path]::GetFullPath($LogPath))
        if (!(Test-Path -LiteralPath $billingLogDirectory)) { New-Item -ItemType Directory -Path $billingLogDirectory -Force | Out-Null }
        Add-Content -LiteralPath $LogPath -Value $billingOutput -Encoding utf8
    }
    $billingOutput
    exit $billingCode
} finally { $env:GULHOUSE_DATABASE = $savedBillingDatabase }
