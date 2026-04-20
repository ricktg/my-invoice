# My Invoice

[![PHP](https://img.shields.io/badge/PHP-8.4-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![Symfony](https://img.shields.io/badge/Symfony-8-000000?logo=symfony&logoColor=white)](https://symfony.com/)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16-4169E1?logo=postgresql&logoColor=white)](https://www.postgresql.org/)
[![Docker](https://img.shields.io/badge/Docker-Compose-2496ED?logo=docker&logoColor=white)](https://www.docker.com/)
[![CI](https://github.com/ricktg/my-invoice/actions/workflows/ci.yml/badge.svg)](https://github.com/ricktg/my-invoice/actions/workflows/ci.yml)
[![Última release](https://img.shields.io/github/v/release/ricktg/my-invoice?display_name=tag&sort=semver)](https://github.com/ricktg/my-invoice/releases)
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

## Requisitos mínimos

- Git 2.39+
- Docker Desktop 4.20+ (ou Docker Engine compatível)
- Docker Compose v2 (`docker compose`)
- Bash (Linux/macOS) ou PowerShell 7+ (Windows)
- Porta `8282` livre para a aplicação local

> Os scripts do projeto validam automaticamente a presença de Docker e Docker Compose.

## Instalação local (apenas scripts)

### Linux/macOS (Bash)

1. Suba a stack:

```bash
./scripts/dev.sh up
```

2. Instale dependências:

```bash
./scripts/dev.sh install
```

3. Rode migrations:

```bash
./scripts/dev.sh migrate
```

4. Compile os assets:

```bash
./scripts/dev.sh assets
```

5. Acesse:

- `http://localhost:8282`

### Windows (PowerShell)

1. Suba a stack:

```powershell
.\scripts\dev.ps1 up
```

2. Instale dependências:

```powershell
.\scripts\dev.ps1 install
```

3. Rode migrations:

```powershell
.\scripts\dev.ps1 migrate
```

4. Compile os assets:

```powershell
.\scripts\dev.ps1 assets
```

5. Acesse:

- `http://localhost:8282`

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
# Linux/macOS
./scripts/dev.sh cache-clear
./scripts/dev.sh console debug:router
./scripts/dev.sh test
./scripts/dev.sh coverage
./scripts/dev.sh console lint:twig templates
```

```powershell
# Windows PowerShell
.\scripts\dev.ps1 cache-clear
.\scripts\dev.ps1 console debug:router
.\scripts\dev.ps1 test
.\scripts\dev.ps1 coverage
.\scripts\dev.ps1 console lint:twig templates
```

Ajuda completa dos scripts:

```bash
./scripts/dev.sh help
```

```powershell
.\scripts\dev.ps1 help
```

## Upgrade da aplicação por versão

Esta seção descreve como atualizar a aplicação para uma versão específica (tag Git) usando apenas scripts.

### Pré-requisitos para upgrade

- Requisitos mínimos da seção anterior
- Executar na raiz do repositório
- Tag existente no remoto (ex.: `v1.3.0`)

### Script Bash (macOS/Linux)

Arquivo: `scripts/upgrade.sh`

```bash
./scripts/upgrade.sh <versao>
```

Exemplo:

```bash
./scripts/upgrade.sh v1.3.0
```

Com alterações locais não commitadas:

```bash
./scripts/upgrade.sh v1.3.0 --allow-dirty
```

### Script PowerShell (Windows/macOS/Linux)

Arquivo: `scripts/upgrade.ps1`

```powershell
.\scripts\upgrade.ps1 -Version <versao>
```

Exemplo:

```powershell
.\scripts\upgrade.ps1 -Version v1.3.0
```

```powershell
.\scripts\upgrade.ps1 -Version v1.3.0 -AllowDirty
```

### O que os scripts de upgrade fazem automaticamente

1. Validam pré-requisitos (`git`, `docker`, `docker compose`).
2. Buscam tags remotas.
3. Criam branch local `upgrade/<versao>` a partir da tag.
4. Sobem/atualizam containers.
5. Instalam dependências.
6. Executam migrations.
7. Compilam assets.
8. Limpam cache.

### Fluxo recomendado antes de atualizar em produção

1. Executar o upgrade em ambiente de homologação.
2. Validar login, criação/edição de invoice, PDF e envio de e-mail.
3. Conferir migrations aplicadas sem erro.
4. Só então repetir o processo no ambiente de produção.

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
