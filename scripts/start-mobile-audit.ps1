param([ValidatePattern('^[a-z0-9-]+$')][string]$Label='root')
$ErrorActionPreference='Stop'
$repo=(Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$php='D:/XAMPP/php/php.exe'
$dir=Join-Path $repo ('.e2e/mobile/'+$Label)
New-Item -ItemType Directory -Force $dir | Out-Null
$names=@('GULHOUSE_TEST_DATABASE','GULHOUSE_DATABASE','GULHOUSE_E2E_USER','GULHOUSE_E2E_PASS','GULHOUSE_OCR_PYTHON')
$saved=@{};foreach($name in $names){$saved[$name]=[Environment]::GetEnvironmentVariable($name,'Process')}
Push-Location $repo
try {
    $runtimePath=Join-Path $dir 'runtime.json'
    if(Test-Path $runtimePath){
        $previous=Get-Content -Raw $runtimePath | ConvertFrom-Json
        $running=Get-Process -Id $previous.server_pid -ErrorAction SilentlyContinue
        if($running){throw 'An existing audit server still owns this label; use another label or stop the verified owned server.'}
    }
    $env:GULHOUSE_TEST_DATABASE='gulhouse_test_mobile_'+$Label.Replace('-','_')+'_'+(Get-Date -Format 'yyyyMMdd_HHmmss_fff')
    $env:GULHOUSE_E2E_USER='codex_trial';$env:GULHOUSE_E2E_PASS=[guid]::NewGuid().ToString('N')
    $fixtureRaw=& $php scripts/setup-gulhouse-test.php --demo
    if($LASTEXITCODE -ne 0){throw 'Synthetic fixture setup failed.'}
    $fixture=$fixtureRaw | ConvertFrom-Json
    $env:GULHOUSE_DATABASE=$fixture.database
    $env:GULHOUSE_OCR_PYTHON='C:/Users/TKM/.cache/codex-runtimes/codex-primary-runtime/dependencies/python/python.exe'
    $listener=[System.Net.Sockets.TcpListener]::new([System.Net.IPAddress]::Loopback,0)
    $listener.Start();$port=$listener.LocalEndpoint.Port;$listener.Stop()
    $url='http://127.0.0.1:'+$port
    $server=Start-Process -FilePath $php -ArgumentList @('-S',('127.0.0.1:'+$port),'-t','.', 'scripts/dev-router.php') -WorkingDirectory $repo -WindowStyle Hidden -RedirectStandardOutput (Join-Path $dir 'server.log') -RedirectStandardError (Join-Path $dir 'server-errors.log') -PassThru
    @{schema=$fixture.database;url=$url;username=$env:GULHOUSE_E2E_USER;password=$env:GULHOUSE_E2E_PASS;server_pid=$server.Id;fixture=$fixture} | ConvertTo-Json -Depth 4 | Set-Content -Encoding utf8 $runtimePath
    $ready=$false
    for($attempt=0;$attempt -lt 25;$attempt++){
        try{$ready=(Invoke-WebRequest ($url+'/master/index.php/login') -TimeoutSec 2).StatusCode -eq 200}catch{}
        if($ready){break};Start-Sleep -Milliseconds 200
    }
    if(!$ready){Stop-Process -Id $server.Id;throw 'Audit server failed readiness.'}
    Write-Output ('Ready: '+$url+'; private runtime: '+$runtimePath+'; synthetic schema only.')
} finally {
    foreach($name in $names){[Environment]::SetEnvironmentVariable($name,$saved[$name],'Process')}
    Pop-Location
}
