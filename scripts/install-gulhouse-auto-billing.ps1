param(
    [Parameter(Mandatory=$true)][ValidatePattern('^(?:gulhouse_test_[A-Za-z0-9_]+|gulhouse_db)$')][string]$Database,
    [switch]$Install,
    [switch]$AllowRealDatabase,
    [ValidateRange(2,31)][int]$LeadDays = 7,
    [string]$Php = 'D:\XAMPP\php\php.exe'
)
$ErrorActionPreference = 'Stop'
if ($Database -eq 'gulhouse_db' -and !$AllowRealDatabase) { throw 'Real scheduler requires explicit -AllowRealDatabase.' }
if (!(Test-Path -LiteralPath $Php)) { throw 'PHP executable not found.' }
$billingRoot = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$billingRunner = Join-Path $PSScriptRoot 'run-gulhouse-billing.ps1'
$billingLog = Join-Path $billingRoot ('.deploy\billing-logs\auto-' + $Database + '.jsonl')
$billingName = 'GulHouse Daily Lifecycle Billing - ' + $Database
$billingZone = [TimeZoneInfo]::FindSystemTimeZoneById('SE Asia Standard Time')
$billingNow = [TimeZoneInfo]::ConvertTimeFromUtc([DateTime]::UtcNow, $billingZone)
# First run tomorrow avoids running a catch-up write during installation.
$billingStart = $billingNow.Date.AddDays(1).AddHours(9).ToString('yyyy-MM-ddTHH:mm:ss') + '+07:00'
$billingUser = [Security.Principal.WindowsIdentity]::GetCurrent().User.Value
$billingCommand = Join-Path $env:SystemRoot 'System32\WindowsPowerShell\v1.0\powershell.exe'
$billingArguments = '-NoProfile -NonInteractive -WindowStyle Hidden -ExecutionPolicy Bypass -File "' + $billingRunner + '" -Database "' + $Database + '" -Automatic -LeadDays ' + $LeadDays + ' -Apply -Php "' + $Php + '" -LogPath "' + $billingLog + '"'
if ($AllowRealDatabase) { $billingArguments += ' -AllowRealDatabase' }
$escape = { param($value) [Security.SecurityElement]::Escape($value) }
$billingXml = @"
<?xml version="1.0" encoding="UTF-16"?>
<Task version="1.4" xmlns="http://schemas.microsoft.com/windows/2004/02/mit/task">
  <RegistrationInfo><Description>GulHouse automatic lifecycle billing: $Database, H-$LeadDays, Asia/Jakarta</Description></RegistrationInfo>
  <Triggers><CalendarTrigger><StartBoundary>$billingStart</StartBoundary><Enabled>true</Enabled><ScheduleByDay><DaysInterval>1</DaysInterval></ScheduleByDay></CalendarTrigger></Triggers>
  <Principals><Principal id="Author"><UserId>$billingUser</UserId><LogonType>InteractiveToken</LogonType><RunLevel>LeastPrivilege</RunLevel></Principal></Principals>
  <Settings><MultipleInstancesPolicy>IgnoreNew</MultipleInstancesPolicy><DisallowStartIfOnBatteries>false</DisallowStartIfOnBatteries><StopIfGoingOnBatteries>false</StopIfGoingOnBatteries><StartWhenAvailable>true</StartWhenAvailable><Hidden>true</Hidden><ExecutionTimeLimit>PT10M</ExecutionTimeLimit></Settings>
  <Actions Context="Author"><Exec><Command>$(& $escape $billingCommand)</Command><Arguments>$(& $escape $billingArguments)</Arguments><WorkingDirectory>$(& $escape $billingRoot)</WorkingDirectory></Exec></Actions>
</Task>
"@
$billingService = New-Object -ComObject 'Schedule.Service'
$billingService.Connect()
$billingDefinition = $billingService.NewTask(0)
$billingDefinition.XmlText = $billingXml
if (!$Install) {
    [pscustomobject]@{mode='preview';task=$billingName;database=$Database;first_run=$billingStart;schedule='Daily 09:00 Asia/Jakarta';lead_days=$LeadDays;hidden=$true;run_level='LeastPrivilege';xml_validated=$true;installed=$false} | ConvertTo-Json -Compress
    exit 0
}
if (Get-ScheduledTask -TaskName $billingName -ErrorAction SilentlyContinue) { throw 'Task already exists; refusing to replace it.' }
Register-ScheduledTask -TaskName $billingName -Xml $billingXml | Out-Null
[pscustomobject]@{mode='installed';task=$billingName;database=$Database;first_run=$billingStart;schedule='Daily 09:00 Asia/Jakarta';lead_days=$LeadDays;hidden=$true;run_level='LeastPrivilege'} | ConvertTo-Json -Compress
