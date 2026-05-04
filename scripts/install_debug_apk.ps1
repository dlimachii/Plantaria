param(
    [string] $ApkPath = "",
    [string] $AdbPath = ""
)

$ErrorActionPreference = "Stop"

$rootDir = Resolve-Path (Join-Path $PSScriptRoot "..")

if ([string]::IsNullOrWhiteSpace($ApkPath)) {
    $ApkPath = Join-Path $rootDir "android\app\build\outputs\apk\debug\app-debug.apk"
}

if ([string]::IsNullOrWhiteSpace($AdbPath)) {
    $pathAdb = Get-Command adb -ErrorAction SilentlyContinue
    if ($pathAdb) {
        $AdbPath = $pathAdb.Source
    } else {
        $defaultAdbPath = Join-Path $env:LOCALAPPDATA "Android\Sdk\platform-tools\adb.exe"
        if (Test-Path $defaultAdbPath) {
            $AdbPath = $defaultAdbPath
        }
    }
}

if ([string]::IsNullOrWhiteSpace($AdbPath) -or -not (Test-Path $AdbPath)) {
    throw "adb no esta disponible. Esperado en PATH o en $env:LOCALAPPDATA\Android\Sdk\platform-tools\adb.exe."
}

if (-not (Test-Path $ApkPath)) {
    throw "No existe el APK: $ApkPath. Compilalo antes desde WSL: cd android && ./gradlew :app:assembleDebug"
}

Write-Host "==> Dispositivos ADB"
& $AdbPath devices

& $AdbPath get-state *> $null
if ($LASTEXITCODE -ne 0) {
    throw "No hay ningun dispositivo ADB listo. Acepta la depuracion USB en el movil y repite adb devices."
}

Write-Host "==> Preparando adb reverse tcp:8000 tcp:8000"
& $AdbPath reverse tcp:8000 tcp:8000 | Out-Null

Write-Host "==> Instalando APK"
& $AdbPath install -r $ApkPath

Write-Host "APK instalado desde: $ApkPath"
Write-Host "El movil queda preparado para usar http://127.0.0.1:8000/api/ mediante adb reverse."
