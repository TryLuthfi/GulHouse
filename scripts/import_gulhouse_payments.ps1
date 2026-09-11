param(
    [string]$ExcelPath = 'C:\Users\TKM\Pictures\GUL HOUSE\DATA PEMBAYARAN.xlsx',
    [string]$MysqlPath = 'D:\XAMPP\mysql\bin\mysql.exe',
    [string]$Database = 'gulhouse_db',
    [string]$HostName = '127.0.0.1',
    [string]$UserName = 'root',
    [string]$Password = ''
)

$ErrorActionPreference = 'Stop'

function Escape-Sql([object]$value) {
    if ($null -eq $value) { return 'NULL' }
    $text = [string]$value
    if ($text.Trim() -eq '') { return 'NULL' }
    return "'" + $text.Replace('\', '\\').Replace("'", "''") + "'"
}

function Money-To-Int([object]$value) {
    if ($null -eq $value) { return $null }
    $text = ([string]$value).Replace('Rp', '').Replace('.', '').Replace(',', '').Trim()
    if ($text -eq '' -or $text -eq '-') { return $null }
    $clean = $text -replace '[^0-9]', ''
    if ($clean -eq '') { return $null }
    return [int64]$clean
}

function Normalize-Key([object]$value) {
    if ($null -eq $value) { return '' }
    $text = ([string]$value).ToUpperInvariant().Normalize([Text.NormalizationForm]::FormD)
    $chars = $text.ToCharArray() | Where-Object {
        [Globalization.CharUnicodeInfo]::GetUnicodeCategory($_) -ne [Globalization.UnicodeCategory]::NonSpacingMark
    }
    return (($chars -join '') -replace '[^A-Z0-9]', '')
}

function Month-Number([string]$month) {
    switch -Regex ($month.ToUpperInvariant()) {
        'JAN' { return 1 }
        'FEB' { return 2 }
        'MAR' { return 3 }
        'APR' { return 4 }
        'MAY|MEI' { return 5 }
        'JUN' { return 6 }
        'JUL' { return 7 }
        'AUG|AGU' { return 8 }
        'SEP' { return 9 }
        'OCT|OKT' { return 10 }
        'NOV' { return 11 }
        'DEC|DES' { return 12 }
        default { return $null }
    }
}

function Parse-Date-Text([object]$value, [int]$periodMonth, [int]$periodYear) {
    $text = ([string]$value).Trim()
    if ($text -eq '' -or $text -eq '-') { return $null }
    if ($text -match '^(\d{1,2})[-/\s]([A-Za-z]{3,9})(?:[-/\s](\d{2,4}))?$') {
        $day = [int]$matches[1]
        $month = Month-Number $matches[2]
        if ($null -eq $month) { return $null }
        $year = $periodYear
        if ($matches[3]) {
            $year = [int]$matches[3]
            if ($year -lt 100) { $year += 2000 }
        } elseif (($month - $periodMonth) -gt 6) {
            $year--
        } elseif (($periodMonth - $month) -gt 6) {
            $year++
        }
        try {
            return (Get-Date -Year $year -Month $month -Day $day).ToString('yyyy-MM-dd')
        } catch {
            return $null
        }
    }
    return $null
}

function Db-Args() {
    $args = @("--host=$HostName", "--user=$UserName", "--database=$Database", '--batch', '--raw', '--default-character-set=utf8mb4')
    if ($Password -ne '') {
        $args += "--password=$Password"
    }
    return $args
}

if (!(Test-Path -LiteralPath $ExcelPath)) {
    throw "Excel file not found: $ExcelPath"
}

$mysqlArgs = Db-Args
$roomsRaw = & $MysqlPath @mysqlArgs --execute="SELECT p.code, p.name, r.id, r.room_label FROM gh_rooms r JOIN gh_properties p ON p.id = r.property_id;"
$tenantsRaw = & $MysqlPath @mysqlArgs --execute="SELECT id, fullname, COALESCE(phone, '') phone FROM gh_tenants;"

$rooms = @{}
$roomsRaw | Select-Object -Skip 1 | ForEach-Object {
    $parts = $_ -split "`t"
    $roomId = [int]$parts[2]
    $codeKey = (Normalize-Key $parts[0]) + '|' + (Normalize-Key $parts[3])
    $nameKey = (Normalize-Key $parts[1]) + '|' + (Normalize-Key $parts[3])
    $rooms[$codeKey] = $roomId
    $rooms[$nameKey] = $roomId
}

$tenantsByPhone = @{}
$tenantsByName = @{}
$tenantsRaw | Select-Object -Skip 1 | ForEach-Object {
    $parts = $_ -split "`t"
    if ($parts[2]) {
        $tenantsByPhone[(Normalize-Key $parts[2])] = [int]$parts[0]
    }
    $nameKey = Normalize-Key $parts[1]
    if ($nameKey -and !$tenantsByName.ContainsKey($nameKey)) {
        $tenantsByName[$nameKey] = [int]$parts[0]
    }
}

$sourceFile = Split-Path -Leaf $ExcelPath
$periods = @(
    @{ Label = 'SEPTEMBER 2025'; Key = '2025-09'; Month = 9; Year = 2025; Col = 6 },
    @{ Label = 'OKTOBER 2025'; Key = '2025-10'; Month = 10; Year = 2025; Col = 17 },
    @{ Label = 'NOVEMBER 2025'; Key = '2025-11'; Month = 11; Year = 2025; Col = 28 },
    @{ Label = 'DESEMBER 2025'; Key = '2025-12'; Month = 12; Year = 2025; Col = 39 },
    @{ Label = 'JANUARI 2026'; Key = '2026-01'; Month = 1; Year = 2026; Col = 51 },
    @{ Label = 'FEBRUARI 2026'; Key = '2026-02'; Month = 2; Year = 2026; Col = 63 },
    @{ Label = 'MARET 2026'; Key = '2026-03'; Month = 3; Year = 2026; Col = 75 },
    @{ Label = 'APRIL 2026'; Key = '2026-04'; Month = 4; Year = 2026; Col = 87 },
    @{ Label = 'MEI 2026'; Key = '2026-05'; Month = 5; Year = 2026; Col = 99 },
    @{ Label = 'JUNI 2026'; Key = '2026-06'; Month = 6; Year = 2026; Col = 111 },
    @{ Label = 'JULI 2026'; Key = '2026-07'; Month = 7; Year = 2026; Col = 123 },
    @{ Label = 'AGUSTUS 2026'; Key = '2026-08'; Month = 8; Year = 2026; Col = 135 },
    @{ Label = 'SEPTEMBER 2026'; Key = '2026-09'; Month = 9; Year = 2026; Col = 147 }
)

$excel = New-Object -ComObject Excel.Application
$excel.Visible = $false
$excel.DisplayAlerts = $false
$workbook = $excel.Workbooks.Open($ExcelPath, 0, $true)
$sheet = $workbook.Worksheets.Item('DATABASE')

$stat = [ordered]@{
    periods = 0
    bills = 0
    payments = 0
    issues = 0
    skipped_no_room = 0
}

$sql = New-Object System.Collections.Generic.List[string]
$sql.Add('SET NAMES utf8mb4;')
$sql.Add('START TRANSACTION;')
$sql.Add("DELETE FROM gh_payment_import_issues WHERE source_file = $(Escape-Sql $sourceFile);")

foreach ($period in $periods) {
    $sql.Add("INSERT INTO gh_billing_periods (period_key, period_label, period_month, period_year, source_file) VALUES ($(Escape-Sql $period.Key), $(Escape-Sql $period.Label), $($period.Month), $($period.Year), $(Escape-Sql $sourceFile)) ON DUPLICATE KEY UPDATE period_label = VALUES(period_label), period_month = VALUES(period_month), period_year = VALUES(period_year), source_file = VALUES(source_file);")
    $stat.periods++
}

$currentProperty = ''
for ($row = 8; $row -le 82; $row++) {
    $sectionNo = ([string]$sheet.Cells.Item($row, 2).Text).Trim()
    $type = ([string]$sheet.Cells.Item($row, 3).Text).Trim()
    $roomLabel = ([string]$sheet.Cells.Item($row, 4).Text).Trim()
    $basePrice = Money-To-Int $sheet.Cells.Item($row, 5).Text

    if ($sectionNo -match '^[A-Z]$' -and $type) {
        $currentProperty = switch ($type) {
            'Gulhouse 1' { 'GH 1' }
            'Gulhouse 2' { 'GH 2' }
            default { $type }
        }
        continue
    }

    if (!($sectionNo -match '^\d+$') -or !$roomLabel) {
        continue
    }

    $roomKey = (Normalize-Key $currentProperty) + '|' + (Normalize-Key $roomLabel)
    $roomId = $null
    if ($rooms.ContainsKey($roomKey)) {
        $roomId = $rooms[$roomKey]
    }

    foreach ($period in $periods) {
        $start = [int]$period.Col
        $tenantName = ([string]$sheet.Cells.Item($row, $start).Text).Trim()
        $tenantPhone = ([string]$sheet.Cells.Item($row, $start + 1).Text).Trim()
        $deposit = Money-To-Int $sheet.Cells.Item($row, $start + 3).Text
        $dueText = ([string]$sheet.Cells.Item($row, $start + 4).Text).Trim()
        $lateText = ([string]$sheet.Cells.Item($row, $start + 5).Text).Trim()
        $paymentText = ([string]$sheet.Cells.Item($row, $start + 6).Text).Trim()
        $amount = Money-To-Int $sheet.Cells.Item($row, $start + 7).Text
        $pastDue = Money-To-Int $sheet.Cells.Item($row, $start + 8).Text
        $futureDue = Money-To-Int $sheet.Cells.Item($row, $start + 9).Text
        $note = ([string]$sheet.Cells.Item($row, $start + 10).Text).Trim()
        $dueDate = Parse-Date-Text $dueText $period.Month $period.Year
        $paymentDate = Parse-Date-Text $paymentText $period.Month $period.Year

        if ($null -eq $roomId) {
            $stat.skipped_no_room++
            $stat.issues++
            $sql.Add("INSERT INTO gh_payment_import_issues (source_file, source_sheet, source_row, source_period, room_label, tenant_name, issue_type, issue_message) VALUES ($(Escape-Sql $sourceFile), 'DATABASE', $row, $(Escape-Sql $period.Label), $(Escape-Sql $roomLabel), $(Escape-Sql $tenantName), 'room_not_found', $(Escape-Sql ('Room not found for property ' + $currentProperty + ' / ' + $roomLabel)));")
            continue
        }

        $tenantId = $null
        $tenantKey = Normalize-Key $tenantName
        $phoneKey = Normalize-Key $tenantPhone
        if ($phoneKey -and $tenantsByPhone.ContainsKey($phoneKey)) {
            $tenantId = $tenantsByPhone[$phoneKey]
        } elseif ($tenantKey -and $tenantKey -notin @('KOSONG', 'MENUNGGU', 'DIBOOKING') -and $tenantsByName.ContainsKey($tenantKey)) {
            $tenantId = $tenantsByName[$tenantKey]
        }

        $billStatus = 'unknown'
        if ($tenantKey -eq 'KOSONG' -or $tenantKey -eq '') {
            $billStatus = 'empty'
        } elseif ($tenantKey -match 'MESS|INTERNAL|BABINSA|KAMARANTO|KAMARWARUNGGRATIS|OWNER') {
            $billStatus = 'internal'
        } elseif ($tenantKey -eq 'MENUNGGU' -or $tenantKey -eq 'DIBOOKING') {
            $billStatus = 'reserved'
        } elseif (($null -ne $amount) -and $amount -gt 0 -and ($null -ne $basePrice) -and $amount -ge $basePrice) {
            $billStatus = 'paid'
        } elseif (($null -ne $amount) -and $amount -gt 0) {
            $billStatus = 'partial'
        } elseif ($tenantKey) {
            $billStatus = 'unpaid'
        }

        $paidTotal = if ($null -ne $amount) { $amount } else { 0 }
        $pastDueValue = if ($null -ne $pastDue) { $pastDue } else { 0 }
        $futureDueValue = if ($null -ne $futureDue) { $futureDue } else { 0 }
        $vacantValue = if ($billStatus -eq 'empty' -and $null -ne $basePrice) { $basePrice } else { 0 }
        $tenantSql = if ($null -ne $tenantId) { [string]$tenantId } else { 'NULL' }
        $basePriceSql = if ($null -ne $basePrice) { [string]$basePrice } else { 'NULL' }
        $depositSql = if ($null -ne $deposit) { [string]$deposit } else { 'NULL' }
        $dueDateSql = Escape-Sql $dueDate

        $sql.Add("INSERT INTO gh_room_bills (period_id, room_id, tenant_id, tenant_name_snapshot, tenant_phone_snapshot, base_price, deposit_amount, due_date, due_date_text, paid_total, late_days_text, past_due_amount, future_due_amount, vacant_amount, bill_status, source_sheet, source_row, source_period, notes) SELECT bp.id, $roomId, $tenantSql, $(Escape-Sql $tenantName), $(Escape-Sql $tenantPhone), $basePriceSql, $depositSql, $dueDateSql, $(Escape-Sql $dueText), $paidTotal, $(Escape-Sql $lateText), $pastDueValue, $futureDueValue, $vacantValue, $(Escape-Sql $billStatus), 'DATABASE', $row, $(Escape-Sql $period.Label), $(Escape-Sql $note) FROM gh_billing_periods bp WHERE bp.period_key = $(Escape-Sql $period.Key) ON DUPLICATE KEY UPDATE tenant_id = VALUES(tenant_id), tenant_name_snapshot = VALUES(tenant_name_snapshot), tenant_phone_snapshot = VALUES(tenant_phone_snapshot), base_price = VALUES(base_price), deposit_amount = VALUES(deposit_amount), due_date = VALUES(due_date), due_date_text = VALUES(due_date_text), paid_total = VALUES(paid_total), late_days_text = VALUES(late_days_text), past_due_amount = VALUES(past_due_amount), future_due_amount = VALUES(future_due_amount), vacant_amount = VALUES(vacant_amount), bill_status = VALUES(bill_status), source_row = VALUES(source_row), source_period = VALUES(source_period), notes = VALUES(notes);")
        $stat.bills++

        if (($null -ne $amount) -and $amount -gt 0) {
            $paymentDateSql = Escape-Sql $paymentDate
            $sourceKey = 'DATA PEMBAYARAN|DATABASE|' + $period.Key + '|' + $roomId + '|' + $row
            $sql.Add("INSERT INTO gh_payments (room_bill_id, payment_date, payment_date_text, amount, payment_type, source_key, notes) SELECT rb.id, $paymentDateSql, $(Escape-Sql $paymentText), $amount, 'rent', $(Escape-Sql $sourceKey), $(Escape-Sql $note) FROM gh_room_bills rb JOIN gh_billing_periods bp ON bp.id = rb.period_id WHERE bp.period_key = $(Escape-Sql $period.Key) AND rb.room_id = $roomId ON DUPLICATE KEY UPDATE room_bill_id = VALUES(room_bill_id), payment_date = VALUES(payment_date), payment_date_text = VALUES(payment_date_text), amount = VALUES(amount), notes = VALUES(notes);")
            $stat.payments++
            if (!$paymentText) {
                $stat.issues++
                $sql.Add("INSERT INTO gh_payment_import_issues (source_file, source_sheet, source_row, source_period, room_label, tenant_name, issue_type, issue_message) VALUES ($(Escape-Sql $sourceFile), 'DATABASE', $row, $(Escape-Sql $period.Label), $(Escape-Sql $roomLabel), $(Escape-Sql $tenantName), 'payment_without_date', 'Payment amount exists but payment date is empty.');")
            }
        }

        if ($tenantKey -and $tenantKey -notin @('KOSONG', 'MENUNGGU', 'DIBOOKING') -and $null -eq $tenantId -and $tenantKey -notmatch 'MESS|INTERNAL|BABINSA|KAMARANTO|KAMARWARUNGGRATIS|OWNER') {
            $stat.issues++
            $sql.Add("INSERT INTO gh_payment_import_issues (source_file, source_sheet, source_row, source_period, room_label, tenant_name, issue_type, issue_message) VALUES ($(Escape-Sql $sourceFile), 'DATABASE', $row, $(Escape-Sql $period.Label), $(Escape-Sql $roomLabel), $(Escape-Sql $tenantName), 'tenant_not_matched', 'Tenant from payment sheet could not be matched to gh_tenants.');")
        }
    }
}

$sql.Add('COMMIT;')

if (!(Test-Path -LiteralPath '.deploy')) {
    New-Item -ItemType Directory -Path '.deploy' | Out-Null
}

$outFile = Join-Path '.deploy' ('gulhouse_payments_import_' + (Get-Date -Format 'yyyyMMdd_HHmmss') + '.sql')
$sql | Set-Content -Path $outFile -Encoding UTF8
Get-Content -Raw -Path $outFile | & $MysqlPath @mysqlArgs

[pscustomobject]$stat | Format-List
Write-Output "SQL file: $outFile"

$workbook.Close($false)
$excel.Quit()
[System.Runtime.Interopservices.Marshal]::ReleaseComObject($sheet) | Out-Null
[System.Runtime.Interopservices.Marshal]::ReleaseComObject($workbook) | Out-Null
[System.Runtime.Interopservices.Marshal]::ReleaseComObject($excel) | Out-Null
