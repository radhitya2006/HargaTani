$ErrorActionPreference = 'Stop'
$projectRoot = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$php = (Get-Command php.exe -CommandType Application | Select-Object -First 1).Source
$phpWithoutConsole = Join-Path (Split-Path $php -Parent) 'php-win.exe'
if (-not (Test-Path -LiteralPath $phpWithoutConsole -PathType Leaf)) {
    throw "php-win.exe tidak ditemukan di folder PHP Laragon: $phpWithoutConsole"
}
$taskName = 'HargaTani - Laravel Scheduler'
$account = [System.Security.Principal.WindowsIdentity]::GetCurrent().Name
$arguments = 'artisan schedule:run'
$action = New-ScheduledTaskAction -Execute $phpWithoutConsole -Argument $arguments -WorkingDirectory $projectRoot

$existing = Get-ScheduledTask -TaskPath '\' | Where-Object TaskName -eq $taskName
if ($existing) {
    $currentAction = $existing.Actions[0]
    if ($existing.Actions.Count -ne 1 -or $currentAction.Execute -notin @($php, $phpWithoutConsole) -or
        $currentAction.WorkingDirectory -ne $projectRoot -or $currentAction.Arguments -ne $arguments) {
        throw "Nama tugas sudah digunakan untuk konfigurasi berbeda: $taskName"
    }
    if ($currentAction.Execute -eq $php) {
        Set-ScheduledTask -TaskName $taskName -TaskPath '\' -Action $action
        return
    }
    Write-Output "Tugas sudah terpasang: $taskName"
    return
}

$trigger = New-ScheduledTaskTrigger -Once -At (Get-Date).AddMinutes(1) -RepetitionInterval (New-TimeSpan -Minutes 1)
$principal = New-ScheduledTaskPrincipal -UserId $account -LogonType Interactive -RunLevel Limited
$settings = New-ScheduledTaskSettingsSet -MultipleInstances IgnoreNew -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries
Register-ScheduledTask -TaskName $taskName -TaskPath '\' -Action $action -Trigger $trigger -Principal $principal -Settings $settings
