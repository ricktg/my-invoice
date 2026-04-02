#!/usr/bin/env bash
set -euo pipefail

COMPOSE_FILE="${COMPOSE_FILE:-compose.yaml}"
PHP_SERVICE="${PHP_SERVICE:-phpi}"
APP_URL="${APP_URL:-http://localhost:8282}"

compose() {
  docker compose -f "$COMPOSE_FILE" "$@"
}

phpi() {
  compose exec "$PHP_SERVICE" "$@"
}

usage() {
  cat <<'EOF'
Uso: ./scripts/dev.sh <comando> [args]

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
  cache-clear       Limpa cache do Symfony
  console <args>    Roda php bin/console <args>
  composer <args>   Roda composer <args>
  bash              Abre shell no container PHP
  open              Mostra URL local da aplicação
  help              Mostra esta ajuda

Exemplos:
  ./scripts/dev.sh up
  ./scripts/dev.sh install
  ./scripts/dev.sh migrate
  ./scripts/dev.sh console debug:router
  ./scripts/dev.sh composer outdated
EOF
}

cmd="${1:-help}"
shift || true

case "$cmd" in
  up)
    compose up -d --build
    ;;
  down)
    compose down
    ;;
  restart)
    compose down
    compose up -d --build
    ;;
  logs)
    compose logs -f
    ;;
  ps)
    compose ps
    ;;
  install)
    phpi composer install
    ;;
  migrate)
    phpi php bin/console doctrine:migrations:migrate --no-interaction
    ;;
  test)
    phpi composer run-script test
    ;;
  coverage)
    phpi composer run-script test:coverage
    ;;
  cache-clear)
    phpi php bin/console cache:clear
    ;;
  console)
    if [ "$#" -eq 0 ]; then
      echo "Informe os argumentos do console. Ex.: ./scripts/dev.sh console debug:router" >&2
      exit 1
    fi
    phpi php bin/console "$@"
    ;;
  composer)
    if [ "$#" -eq 0 ]; then
      echo "Informe os argumentos do composer. Ex.: ./scripts/dev.sh composer require pacote" >&2
      exit 1
    fi
    phpi composer "$@"
    ;;
  bash)
    phpi bash
    ;;
  open)
    echo "$APP_URL"
    ;;
  help|-h|--help)
    usage
    ;;
  *)
    echo "Comando inválido: $cmd" >&2
    usage
    exit 1
    ;;
esac
