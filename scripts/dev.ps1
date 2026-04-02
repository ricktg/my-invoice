param(
    [Parameter(Position = 0)]
    [string]$Command = "help",

    [Parameter(ValueFromRemainingArguments = $true)]
    [string[]]$ArgsList
)

$ErrorActionPreference = "Stop"

$ComposeFile = if ($env:COMPOSE_FILE) { $env:COMPOSE_FILE } else { "compose.yaml" }
$PhpService = if ($env:PHP_SERVICE) { $env:PHP_SERVICE } else { "phpi" }
$AppUrl = if ($env:APP_URL) { $env:APP_URL } else { "http://localhost:8282" }

function Test-DockerAvailable {
    if (-not (Get-Command docker -ErrorAction SilentlyContinue)) {
        throw @"
Docker não foi encontrado no PATH.
Instale o Docker Desktop e tente novamente:
https://www.docker.com/products/docker-desktop/
"@
    }

    $null = & docker compose version 2>$null
    if ($LASTEXITCODE -ne 0) {
        throw @"
O comando 'docker compose' não está disponível.
Atualize o Docker Desktop (Compose v2) e tente novamente.
"@
    }
}

function Invoke-Compose {
    param([string[]]$ComposeArgs)
    & docker compose -f $ComposeFile @ComposeArgs
}

function Invoke-Php {
    param([string[]]$PhpArgs)
    Invoke-Compose -ComposeArgs (@("exec", $PhpService) + $PhpArgs)
}

function Show-Usage {
    @"
Uso: .\scripts\dev.ps1 <comando> [args]

Comandos:
  up                Sobe os containers em background
  down              Derruba os containers
  restart           Reinicia containers
  logs              Mostra logs (follow)
  ps                Lista status dos containers
  install           Instala dependências Composer
  migrate           Executa migrations
  test              Executa testes (composer run-script test)
  coverage          Executa testes com cobertura
  assets            Compila assets (asset-map:compile)
  cache-clear       Limpa cache do Symfony
  console <args>    Roda php bin/console <args>
  composer <args>   Roda composer <args>
  bash              Abre shell no container PHP
  open              Mostra URL local da aplicação
  help              Mostra esta ajuda

Exemplos:
  .\scripts\dev.ps1 up
  .\scripts\dev.ps1 install
  .\scripts\dev.ps1 migrate
  .\scripts\dev.ps1 console debug:router
  .\scripts\dev.ps1 composer outdated
"@
}

switch ($Command) {
    "up" { Test-DockerAvailable; Invoke-Compose @("up", "-d", "--build") }
    "down" { Test-DockerAvailable; Invoke-Compose @("down") }
    "restart" {
        Test-DockerAvailable
        Invoke-Compose @("down")
        Invoke-Compose @("up", "-d", "--build")
    }
    "logs" { Test-DockerAvailable; Invoke-Compose @("logs", "-f") }
    "ps" { Test-DockerAvailable; Invoke-Compose @("ps") }
    "install" { Test-DockerAvailable; Invoke-Php @("composer", "install") }
    "migrate" { Test-DockerAvailable; Invoke-Php @("php", "bin/console", "doctrine:migrations:migrate", "--no-interaction") }
    "test" { Test-DockerAvailable; Invoke-Php @("composer", "run-script", "test") }
    "coverage" { Test-DockerAvailable; Invoke-Php @("composer", "run-script", "test:coverage") }
    "assets" { Test-DockerAvailable; Invoke-Php @("php", "bin/console", "asset-map:compile") }
    "cache-clear" { Test-DockerAvailable; Invoke-Php @("php", "bin/console", "cache:clear") }
    "console" {
        Test-DockerAvailable
        if (-not $ArgsList -or $ArgsList.Count -eq 0) {
            throw "Informe os argumentos do console. Ex.: .\scripts\dev.ps1 console debug:router"
        }
        Invoke-Php -PhpArgs (@("php", "bin/console") + $ArgsList)
    }
    "composer" {
        Test-DockerAvailable
        if (-not $ArgsList -or $ArgsList.Count -eq 0) {
            throw "Informe os argumentos do composer. Ex.: .\scripts\dev.ps1 composer require pacote"
        }
        Invoke-Php -PhpArgs (@("composer") + $ArgsList)
    }
    "bash" { Test-DockerAvailable; Invoke-Php @("bash") }
    "open" { Write-Output $AppUrl }
    "help" { Show-Usage }
    "-h" { Show-Usage }
    "--help" { Show-Usage }
    default {
        Write-Error "Comando inválido: $Command"
        Show-Usage
        exit 1
    }
}
