$ErrorActionPreference = 'Continue'
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8

$AppUrl = 'http://localhost:18080'
$RabbitUrl = 'http://localhost:15672'
$ComposeCmd = $null

function Write-Step([string] $Message) {
    Write-Host $Message
}

function Test-CommandExists([string] $Command) {
    $null -ne (Get-Command $Command -ErrorAction SilentlyContinue)
}

function Invoke-Compose {
    param(
        [Parameter(Mandatory = $true)]
        [string[]] $Arguments
    )

    if ($ComposeCmd.Length -eq 1) {
        & $ComposeCmd[0] @Arguments
    } else {
        & $ComposeCmd[0] $ComposeCmd[1] @Arguments
    }
}

function Invoke-ComposeChecked {
    param(
        [Parameter(Mandatory = $true)]
        [string[]] $Arguments,
        [Parameter(Mandatory = $true)]
        [string] $ErrorMessage
    )

    Invoke-Compose -Arguments $Arguments
    if ($LASTEXITCODE -ne 0) {
        throw $ErrorMessage
    }
}

Write-Step '[1/8] Проверяю Docker...'
if (-not (Test-CommandExists 'docker')) {
    Write-Host 'Docker не найден. Установи Docker Desktop и запусти скрипт снова.'
    exit 1
}

& docker --version | Out-Host
Write-Host 'Docker найден.'

Write-Step '[2/8] Проверяю, запущен ли Docker Engine...'
& docker info *> $null
if ($LASTEXITCODE -ne 0) {
    Write-Host 'Docker установлен, но движок не запущен.'
    Write-Host 'Открой Docker Desktop, дождись запуска и повтори deploy.bat.'
    exit 1
}
Write-Host 'Docker Engine работает.'

Write-Step '[3/8] Проверяю Docker Compose...'
& docker compose version *> $null
if ($LASTEXITCODE -eq 0) {
    $ComposeCmd = @('docker', 'compose')
} elseif (Test-CommandExists 'docker-compose') {
    & docker-compose version *> $null
    if ($LASTEXITCODE -eq 0) {
        $ComposeCmd = @('docker-compose')
    }
}

if ($null -eq $ComposeCmd) {
    Write-Host 'Docker Compose не найден. Обнови Docker Desktop.'
    exit 1
}
Write-Host "Docker Compose найден: $($ComposeCmd -join ' ')"

if (-not (Test-Path -LiteralPath 'docker-compose.yml')) {
    Write-Host 'Файл docker-compose.yml не найден в корне проекта.'
    exit 1
}

Write-Step '[4/8] Проверяю порты...'
$busyPorts = @(18080, 15672) | Where-Object {
    Get-NetTCPConnection -LocalPort $_ -State Listen -ErrorAction SilentlyContinue
} | Select-Object -Unique

if ($busyPorts.Count -gt 0) {
    Write-Host "Порты уже заняты: $($busyPorts -join ', '). Продолжаю запуск: если это не наши контейнеры, Docker Compose покажет ошибку."
} else {
    Write-Host 'Порты свободны.'
}

Write-Step '[5/8] Собираю и запускаю контейнеры...'
Invoke-ComposeChecked -Arguments @('up', '-d', '--build') -ErrorMessage 'Docker Compose не смог запустить контейнеры.'

Write-Step '[6/8] Проверяю Laravel-файлы...'
if (-not (Test-Path -LiteralPath 'src\artisan')) {
    Write-Host 'Laravel-проект еще не создан в папке src.'
    Write-Host 'Окружение поднято, но миграции пока пропускаю.'
    Write-Host 'Создай Laravel через Docker:'
    Write-Host 'docker run --rm -v "%cd%:/app" -w /app composer:2 create-project laravel/laravel src'
    Write-Host ''
    Write-Host '[8/8] Проверяю результат...'
    Invoke-Compose -Arguments @('ps')
    Write-Host ''
    Write-Host 'Деплой инфраструктуры завершен.'
    Write-Host "API будет доступен тут после установки Laravel: $AppUrl"
    Write-Host "RabbitMQ Management: $RabbitUrl"
    Write-Host 'Логин RabbitMQ: guest'
    Write-Host 'Пароль RabbitMQ: guest'
    Write-Host ''
    Write-Host "Логи: $($ComposeCmd -join ' ') logs -f"
    Write-Host "Остановка: $($ComposeCmd -join ' ') down"
    Write-Host "Полный сброс данных: $($ComposeCmd -join ' ') down -v"
    exit 0
}

if (-not (Test-Path -LiteralPath 'src\.env')) {
    Write-Host 'Создаю src\.env из src\.env.example...'
    Copy-Item -LiteralPath 'src\.env.example' -Destination 'src\.env'
}

Write-Step '[7/8] Готовлю Laravel...'
Invoke-ComposeChecked -Arguments @('exec', '-T', 'app', 'composer', 'install', '--no-interaction', '--prefer-dist', '--optimize-autoloader') -ErrorMessage 'composer install завершился с ошибкой.'
Invoke-ComposeChecked -Arguments @('exec', '-T', 'app', 'php', 'artisan', 'key:generate', '--force') -ErrorMessage 'php artisan key:generate завершился с ошибкой.'
Invoke-ComposeChecked -Arguments @('exec', '-T', 'app', 'php', 'artisan', 'migrate', '--seed', '--force') -ErrorMessage 'php artisan migrate --seed завершился с ошибкой.'

Invoke-Compose -Arguments @('exec', '-T', 'app', 'sh', '-lc', "if php artisan list --raw | grep -q '^notifications:setup-broker'; then php artisan notifications:setup-broker --no-interaction; else exit 44; fi")
if ($LASTEXITCODE -eq 44) {
    Write-Host 'Команда notifications:setup-broker пока не реализована. Пропускаю настройку RabbitMQ topology.'
} elseif ($LASTEXITCODE -ne 0) {
    Write-Host 'Команда notifications:setup-broker пока недоступна или завершилась с ошибкой.'
    Write-Host 'Это нормально до реализации RabbitMQ-команд.'
}

Write-Step '[8/8] Проверяю результат...'
Invoke-Compose -Arguments @('ps')

Write-Host ''
Write-Host 'Деплой завершен успешно.'
Write-Host "API доступен тут: $AppUrl"
Write-Host "RabbitMQ Management: $RabbitUrl"
Write-Host 'Логин RabbitMQ: guest'
Write-Host 'Пароль RabbitMQ: guest'
Write-Host ''
Write-Host "Логи: $($ComposeCmd -join ' ') logs -f"
Write-Host "Остановка: $($ComposeCmd -join ' ') down"
Write-Host "Полный сброс данных: $($ComposeCmd -join ' ') down -v"

exit 0




