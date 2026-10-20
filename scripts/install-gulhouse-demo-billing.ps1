param(
    [Parameter(Mandatory=$true)][ValidatePattern('^(?:gulhouse_test_[A-Za-z0-9_]+|gulhouse_db)$')][string]$Database,
    [switch]$Install,
    [switch]$AllowRealDatabase,
    [string]$Php = 'D:\XAMPP\php\php.exe'
)
$ErrorActionPreference = 'Stop'
if ($Database -eq 'gulhouse_db' -and !$AllowRealDatabase) { throw 'Real scheduler requires explicit -AllowRealDatabase; preview is otherwise synthetic-only.' }
if (!(Test-Path -LiteralPath $Php)) { throw 'PHP executable not found.' }
$billingRoot = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$billingRunner = Join-Path $PSScriptRoot 'run-gulhouse-billing.ps1'
$billingLog = Join-Path $billingRoot ('.deploy\billing-logs\' + $Database + '.jsonl')
$billingName = $(if ($Database -eq 'gulhouse_db') { 'GulHouse Monthly Billing - ' } else { 'GulHouse Demo Monthly Billing - ' }) + $Database
$billingTimeZone = [TimeZoneInfo]::FindSystemTimeZoneById('SE Asia Standard Time')
$billingNow = [TimeZoneInfo]::ConvertTimeFromUtc([DateTime]::UtcNow, $billingTimeZone)
$billingFirst = [DateTime]::new($billingNow.Year,$billingNow.Month,1,9,0,0)
if ($billingFirst -le $billingNow) { $billingFirst = $billingFirst.AddMonths(1) }
$billingStart = $billingFirst.ToString('yyyy-MM-ddTHH:mm:ss') + '+07:00'
$billingMonths = '<January/><February/><March/><April/><May/><June/><July/><August/><September/><October/><November/><December/>'
$billingUser = [Security.Principal.WindowsIdentity]::GetCurrent().User.Value
$billingCommand = Join-Path $env:SystemRoot 'System32\WindowsPowerShell\v1.0\powershell.exe'
$billingArguments = '-NoProfile -NonInteractive -WindowStyle Hidden -ExecutionPolicy Bypass -File "' + $billingRunner + '" -Database "' + $Database + '" -Apply -Php "' + $Php + '" -LogPath "' + $billingLog + '"'
if ($AllowRealDatabase) { $billingArguments += ' -AllowRealDatabase' }
$escape = { param($value) [Security.SecurityElement]::Escape($value) }
$billingXml = @"
<?xml version="1.0" encoding="UTF-16"?>
<Task version="1.4" xmlns="http://schemas.microsoft.com/windows/2004/02/mit/task">
  <RegistrationInfo><Description>GulHouse explicit billing target: $Database</Description></RegistrationInfo>
  <Triggers><CalendarTrigger><StartBoundary>$billingStart</StartBoundary><Enabled>true</Enabled><ScheduleByMonth><DaysOfMonth><Day>1</Day></DaysOfMonth><Months>$billingMonths</Months></ScheduleByMonth></CalendarTrigger></Triggers>
  <Principals><Principal id="Author"><UserId>$billingUser</UserId><LogonType>InteractiveToken</LogonType><RunLevel>LeastPrivilege</RunLevel></Principal></Principals>
  <Settings><MultipleInstancesPolicy>IgnoreNew</MultipleInstancesPolicy><DisallowStartIfOnBatteries>false</DisallowStartIfOnBatteries><StopIfGoingOnBatteries>false</StopIfGoingOnBatteries><StartWhenAvailable>true</StartWhenAvailable><Hidden>true</Hidden><ExecutionTimeLimit>PT10M</ExecutionTimeLimit></Settings>
  <Actions Context="Author"><Exec><Command>$(& $escape $billingCommand)</Command><Arguments>$(& $escape $billingArguments)</Arguments><WorkingDirectory>$(& $escape $billingRoot)</WorkingDirectory></Exec></Actions>
</Task>
"@
if (!$Install) {
    # Validate the definition in memory without registering or running a task.
    $billingService = New-Object -ComObject 'Schedule.Service'
    $billingService.Connect()
    $billingDefinition = $billingService.NewTask(0)
    $billingDefinition.XmlText = $billingXml
    [pscustomobject]@{mode='preview';task=$billingName;database=$Database;first_run=$billingStart;schedule='Day 1 monthly at 09:00 Asia/Jakarta';hidden=$true;run_level='LeastPrivilege';production_opt_in=[bool]$AllowRealDatabase;xml_validated=$true;installed=$false} | ConvertTo-Json -Compress
    exit 0
}
if (Get-ScheduledTask -TaskName $billingName -ErrorAction SilentlyContinue) { throw 'Task already exists; refusing to replace it.' }
Register-ScheduledTask -TaskName $billingName -Xml $billingXml | Out-Null
[pscustomobject]@{mode='installed';task=$billingName;database=$Database;first_run=$billingStart;hidden=$true;run_level='LeastPrivilege'} | ConvertTo-Json -Compress
