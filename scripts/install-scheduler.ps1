$ErrorActionPreference = 'Stop'
$projectRoot = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$php = (Get-Command php.exe -CommandType Application | Select-Object -First 1).Source
$taskName = 'HargaTani - Laravel Scheduler'
$account = [System.Security.Principal.WindowsIdentity]::GetCurrent().Name
$arguments = 'artisan schedule:run'

$existing = Get-ScheduledTask -TaskPath '\' | Where-Object TaskName -eq $taskName
if ($existing) {
    $action = $existing.Actions[0]
    if ($action.Execute -ne $php -or $action.WorkingDirectory -ne $projectRoot -or $action.Arguments -ne $arguments) {
        throw "Nama tugas sudah digunakan untuk konfigurasi berbeda: $taskName"
    }
    Write-Output "Tugas sudah terpasang: $taskName"
    return
}

$action = New-ScheduledTaskAction -Execute $php -Argument $arguments -WorkingDirectory $projectRoot
$trigger = New-ScheduledTaskTrigger -Once -At (Get-Date).AddMinutes(1) -RepetitionInterval (New-TimeSpan -Minutes 1)
$principal = New-ScheduledTaskPrincipal -UserId $account -LogonType Interactive -RunLevel Limited
$settings = New-ScheduledTaskSettingsSet -MultipleInstances IgnoreNew -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries
Register-ScheduledTask -TaskName $taskName -TaskPath '\' -Action $action -Trigger $trigger -Principal $principal -Settings $settings
