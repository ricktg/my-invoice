#!/usr/bin/env bash
set -euo pipefail

COMPOSE_FILE="${COMPOSE_FILE:-compose.yaml}"
PHP_SERVICE="${PHP_SERVICE:-phpi}"
APP_URL="${APP_URL:-http://localhost:8282}"

check_docker() {
  if ! command -v docker >/dev/null 2>&1; then
    echo "Docker nao foi encontrado no PATH." >&2
    echo "Instale o Docker Desktop e tente novamente: https://www.docker.com/products/docker-desktop/" >&2
    exit 1
  fi

  if ! docker compose version >/dev/null 2>&1; then
    echo "O comando 'docker compose' nao esta disponivel." >&2
    echo "Atualize o Docker Desktop (Compose v2) e tente novamente." >&2
    exit 1
  fi
}

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
  assets            Compila assets (asset-map:compile)
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
    check_docker
    compose up -d --build
    ;;
  down)
    check_docker
    compose down
    ;;
  restart)
    check_docker
    compose down
    compose up -d --build
    ;;
  logs)
    check_docker
    compose logs -f
    ;;
  ps)
    check_docker
    compose ps
    ;;
  install)
    check_docker
    phpi composer install
    ;;
  migrate)
    check_docker
    phpi php bin/console doctrine:migrations:migrate --no-interaction
    ;;
  test)
    check_docker
    phpi composer run-script test
    ;;
  coverage)
    check_docker
    phpi composer run-script test:coverage
    ;;
  assets)
    check_docker
    phpi php bin/console asset-map:compile
    ;;
  cache-clear)
    check_docker
    phpi php bin/console cache:clear
    ;;
  console)
    check_docker
    if [ "$#" -eq 0 ]; then
      echo "Informe os argumentos do console. Ex.: ./scripts/dev.sh console debug:router" >&2
      exit 1
    fi
    phpi php bin/console "$@"
    ;;
  composer)
    check_docker
    if [ "$#" -eq 0 ]; then
      echo "Informe os argumentos do composer. Ex.: ./scripts/dev.sh composer require pacote" >&2
      exit 1
    fi
    phpi composer "$@"
    ;;
  bash)
    check_docker
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
