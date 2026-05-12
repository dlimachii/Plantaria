param(
    [string] $ApkPath = "",
    [string] $AdbPath = ""
)

$ErrorActionPreference = "Stop"

$rootDir = Resolve-Path (Join-Path $PSScriptRoot "..")
$flavor = $env:PLANTARIA_ANDROID_FLAVOR
if ([string]::IsNullOrWhiteSpace($flavor)) {
    $flavor = "prod"
}

if ([string]::IsNullOrWhiteSpace($ApkPath)) {
    $ApkPath = Join-Path $rootDir "android\app\build\outputs\apk\$flavor\debug\app-$flavor-debug.apk"
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
    $flavorTask = $flavor.Substring(0, 1).ToUpper() + $flavor.Substring(1)
    throw "No existe el APK: $ApkPath. Compilalo antes desde WSL: cd android && ./gradlew :app:assemble${flavorTask}Debug"
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
Write-Host "adb reverse queda preparado para backend local, pero la app solo lo usara si guardas http://127.0.0.1:8000/api/ como servidor."
