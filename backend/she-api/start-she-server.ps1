$ErrorActionPreference = 'SilentlyContinue'

$projectPath = Split-Path -Parent $MyInvocation.MyCommand.Path
$phpPath = 'C:\xampp\php\php.exe'
$mysqlPath = 'C:\xampp\mysql\bin\mysqld.exe'
$mysqlConfigPath = 'C:\xampp\mysql\bin\my.ini'
$mysqlBinPath = 'C:\xampp\mysql\bin'
$logPath = Join-Path $projectPath 'storage\logs'
$serverLog = Join-Path $logPath 'she-server.log'
$serverErrorLog = Join-Path $logPath 'she-server-error.log'
$mysqlLog = Join-Path $logPath 'she-mysql.log'
$mysqlErrorLog = Join-Path $logPath 'she-mysql-error.log'

if (!(Test-Path $logPath)) {
    New-Item -ItemType Directory -Path $logPath -Force | Out-Null
}

function Test-PortListening {
    param([int] $Port)
    return [bool](Get-NetTCPConnection -LocalPort $Port -State Listen -ErrorAction SilentlyContinue)
}

if ((Test-Path $mysqlPath) -and !(Test-PortListening -Port 3306)) {
    $mysqlArgs = @("--defaults-file=$mysqlConfigPath", '--standalone')
    Start-Process -FilePath $mysqlPath `
        -ArgumentList $mysqlArgs `
        -WorkingDirectory $mysqlBinPath `
        -WindowStyle Hidden `
        -RedirectStandardOutput $mysqlLog `
        -RedirectStandardError $mysqlErrorLog

    Start-Sleep -Seconds 5
}

if ((Test-Path $phpPath) -and !(Test-PortListening -Port 82)) {
    Start-Process -FilePath $phpPath `
        -ArgumentList @('artisan', 'serve', '--host=0.0.0.0', '--port=82') `
        -WorkingDirectory $projectPath `
        -WindowStyle Hidden `
        -RedirectStandardOutput $serverLog `
        -RedirectStandardError $serverErrorLog
}
