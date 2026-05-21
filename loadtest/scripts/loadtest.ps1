param(
    [string]$Scenario = "soak"
)

$ErrorActionPreference = "Stop"
$Root = Resolve-Path (Join-Path $PSScriptRoot "..\..")
$ComposeFile = Join-Path $Root "loadtest\docker-compose.loadtest.yml"

function Test-HttpOk {
    param([string]$Url)
    try {
        $response = Invoke-WebRequest -Uri $Url -UseBasicParsing -TimeoutSec 10
        return $response.StatusCode -ge 200 -and $response.StatusCode -lt 300
    } catch {
        return $false
    }
}

$scenarioMap = @{
    "soak" = "/scripts/scenarios/07_soak.js"
}

if (-not $scenarioMap.ContainsKey($Scenario)) {
    Write-Host "Неизвестный сценарий: $Scenario" -ForegroundColor Red
    Write-Host "Доступно: $($scenarioMap.Keys -join ', ')"
    exit 1
}

Write-Host "[1/5] Проверяю Docker..."
docker --version | Out-Host
docker compose version | Out-Host

Write-Host "[2/5] Проверяю API health..."
if (-not (Test-HttpOk "http://localhost:18080/health")) {
    Write-Host "API health не отвечает. Сначала запусти deploy.bat из корня проекта." -ForegroundColor Red
    exit 1
}
Write-Host "API health OK."

Write-Host "[3/5] Запускаю Grafana и InfluxDB..."
Push-Location $Root
try {
    docker compose -f $ComposeFile up -d influxdb grafana
    if ($LASTEXITCODE -ne 0) {
        throw "Не удалось запустить loadtest compose."
    }

    Write-Host "[4/5] Запускаю k6 сценарий: $Scenario..."
    docker compose -f $ComposeFile run --rm k6 run $scenarioMap[$Scenario]
    if ($LASTEXITCODE -ne 0) {
        throw "k6 сценарий завершился с ошибкой."
    }
} finally {
    Pop-Location
}

Write-Host "[5/5] Готово."
Write-Host "Grafana:  http://localhost:3000"
Write-Host "RabbitMQ: http://localhost:15672"
Write-Host "API:      http://localhost:18080"
Write-Host "Stats:    docker compose exec -T app php artisan notifications:stats"
