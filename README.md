# My Invoice

[![PHP](https://img.shields.io/badge/PHP-8.4-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![Symfony](https://img.shields.io/badge/Symfony-8-000000?logo=symfony&logoColor=white)](https://symfony.com/)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16-4169E1?logo=postgresql&logoColor=white)](https://www.postgresql.org/)
[![Docker](https://img.shields.io/badge/Docker-Compose-2496ED?logo=docker&logoColor=white)](https://www.docker.com/)
[![CI](https://github.com/ricktg/my-invoice/actions/workflows/ci.yml/badge.svg)](https://github.com/ricktg/my-invoice/actions/workflows/ci.yml)
[![Dependabot](https://img.shields.io/badge/Dependabot-Ativo-025E8C?logo=dependabot&logoColor=white)](https://github.com/ricktg/my-invoice/security/dependabot)
[![Code Scanning](https://github.com/ricktg/my-invoice/actions/workflows/codeql.yml/badge.svg)](https://github.com/ricktg/my-invoice/actions/workflows/codeql.yml)
[![Secret Scanning](https://github.com/ricktg/my-invoice/actions/workflows/secret-scan.yml/badge.svg)](https://github.com/ricktg/my-invoice/actions/workflows/secret-scan.yml)
[![Cobertura](https://codecov.io/gh/ricktg/my-invoice/graph/badge.svg)](https://codecov.io/gh/ricktg/my-invoice)
[![Licença](https://img.shields.io/badge/Licen%C3%A7a-MIT-green.svg)](LICENSE)

Aplicação open source para gestão de invoices de prestadores de serviço, construída com **Symfony 8 + PostgreSQL + Docker**.

Inclui autenticação, cadastro de empresas/clientes, geração de invoice (daily rate e cobrança única), exportação em PDF, envio por e-mail e histórico de envios.

> Badge de cobertura usa Codecov. Se ainda não estiver configurado, ele pode aparecer como `unknown` até o primeiro upload.

## Funcionalidades

- Cadastro e login de usuários
- Perfil com:
  - descrição do cargo/serviço
  - valor diário padrão
  - moeda padrão do daily rate
- Gestão de empresas (emissora e destinatária)
- Gestão de invoices:
  - criar/editar/excluir
  - mês de referência
  - preenchimento automático de dias úteis para `daily_rate`
  - validação de apenas um item `daily_rate` por invoice
- Geração de PDF da invoice
- Envio do PDF por e-mail
- Status de envio e histórico de envios por invoice

## Stack técnica

- PHP 8.4
- Symfony 8
- Doctrine ORM + Migrations
- PostgreSQL 16
- Twig
- Dompdf
- Docker Compose

## Como rodar localmente (Docker)

1. Suba os containers:

```bash
docker compose up -d --build
```

2. Instale as dependências PHP:

```bash
docker compose exec phpi composer install
```

3. Rode as migrations:

```bash
docker compose exec phpi php bin/console doctrine:migrations:migrate --no-interaction
```

4. Acesse no navegador:

- App: `http://localhost:8282`

## Configuração de ambiente

Use o `.env.example` como base e mantenha segredos em arquivos locais não versionados (recomendado: `.env.local`) ou variáveis de ambiente do sistema:

```bash
cp .env.example .env.local
```

Variáveis importantes:

- `DATABASE_URL`
- `MAILER_DSN`
- `APP_SECRET`
- `SMTP_LOGIN`, `SMTP_PASS`, `BREVO_KEY` (integrações opcionais)

### E-mail (SMTP)

Para envio real de e-mail, configure um DSN SMTP válido, por exemplo:

```dotenv
MAILER_DSN=smtp://user:pass@smtp.example.com:587?encryption=tls&auth_mode=login
```

## Comandos úteis

```bash
# Limpar cache
docker compose exec phpi php bin/console cache:clear

# Listar rotas
docker compose exec phpi php bin/console debug:router

# Rodar migrations
docker compose exec phpi php bin/console doctrine:migrations:migrate --no-interaction

# Validar templates Twig
docker compose exec phpi php bin/console lint:twig templates
```

## Atalhos para Docker

Para facilitar o dia a dia, use o script `scripts/dev.sh`:

```bash
# Subir ambiente
./scripts/dev.sh up

# Instalar dependências
./scripts/dev.sh install

# Rodar migrations
./scripts/dev.sh migrate

# Rodar testes
./scripts/dev.sh test

# Compilar assets
./scripts/dev.sh assets

# Ver ajuda completa
./scripts/dev.sh help
```

No PowerShell, use `scripts/dev.ps1`:

```powershell
.\scripts\dev.ps1 up
.\scripts\dev.ps1 install
.\scripts\dev.ps1 migrate
.\scripts\dev.ps1 test
.\scripts\dev.ps1 assets
.\scripts\dev.ps1 help
```

## Estrutura do projeto

- `src/Entity` - entidades de domínio
- `src/Controller` - ações HTTP
- `src/Form` - formulários e validações
- `templates/` - views Twig
- `migrations/` - migrações de banco
- `compose.yaml` - stack local com Docker

## Screenshots

Adicione seus prints na pasta `docs/screenshots/` com os nomes abaixo:

- `docs/screenshots/login.png`
- `docs/screenshots/invoice-list.png`
- `docs/screenshots/invoice-view.png`

Pré-visualização no GitHub:

![Tela de login](docs/screenshots/login.png)
![Lista de invoices](docs/screenshots/invoice-list.png)
![Visualização da invoice](docs/screenshots/invoice-view.png)

## Roadmap

- [ ] Dashboard com métricas (faturamento mensal, total anual, invoices pendentes)
- [ ] Multi-idioma (PT-BR/EN)
- [ ] Template de e-mail personalizável por usuário
- [ ] Exportação CSV/XLSX de invoices
- [ ] Suporte a múltiplos perfis/empresas por usuário
- [ ] Integração opcional com provedores de pagamento
- [ ] Testes automatizados de integração para fluxo completo de invoice

## Segurança antes de publicar

- Nunca versione credenciais reais/chaves de API.
- Mantenha segredos em `.env.local` ou env vars.
- Se alguma credencial já foi exposta em commit, faça rotação imediatamente.

## Contribuição

Leia [CONTRIBUTING.md](CONTRIBUTING.md) antes de abrir PR.

## Reporte de segurança

Leia [SECURITY.md](SECURITY.md) para reportar vulnerabilidades de forma responsável.

## Licença

Este projeto está sob a licença MIT. Veja [LICENSE](LICENSE).
