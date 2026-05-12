param(
    [string] $AdbPath = ""
)

$ErrorActionPreference = "Stop"

$rootDir = (Resolve-Path (Join-Path $PSScriptRoot "..")).ProviderPath

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

Write-Host "==> Dispositivos ADB"
& $AdbPath devices

& $AdbPath get-state *> $null
if ($LASTEXITCODE -ne 0) {
    throw "No hay ningun dispositivo ADB listo. Acepta la depuracion USB en el movil y repite adb devices."
}

Write-Host "==> Preparando adb reverse tcp:8000 tcp:8000 (si aplica)"
& $AdbPath reverse tcp:8000 tcp:8000 | Out-Null

$flavors = @("demoA", "demoB", "demoC")
$stagingDir = Join-Path $env:TEMP "plantaria-apks"
New-Item -ItemType Directory -Force -Path $stagingDir | Out-Null

foreach ($flavor in $flavors) {
    $apkPath = Join-Path $rootDir "android\app\build\outputs\apk\$flavor\debug\app-$flavor-debug.apk"
    if (-not (Test-Path $apkPath)) {
        $flavorTask = $flavor.Substring(0, 1).ToUpper() + $flavor.Substring(1)
        throw "No existe el APK: $apkPath. Compilalo antes desde WSL: cd android && ./gradlew :app:assemble${flavorTask}Debug"
    }

    $apkLocalPath = Join-Path $stagingDir "app-$flavor-debug.apk"
    Copy-Item -LiteralPath $apkPath -Destination $apkLocalPath -Force

    $remotePath = "/data/local/tmp/plantaria-$flavor-debug.apk"

    Write-Host "==> Subiendo $flavor a $remotePath"
    & $AdbPath push -- $apkLocalPath $remotePath
    if ($LASTEXITCODE -ne 0) {
        throw "Fallo subiendo $flavor (exit=$LASTEXITCODE)."
    }

    Write-Host "==> Instalando $flavor desde $remotePath"
    & $AdbPath shell pm install -r $remotePath
    if ($LASTEXITCODE -ne 0) {
        throw "Fallo instalando $flavor (exit=$LASTEXITCODE)."
    }

    & $AdbPath shell rm -f $remotePath | Out-Null
}

Write-Host "OK: demoA/demoB/demoC instaladas."
