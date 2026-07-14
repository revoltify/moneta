![Moneta dashboard](.github/screenshot.png)

![Laravel 13](https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel&logoColor=white)
![PHP 8.3+](https://img.shields.io/badge/PHP-8.3%2B-777BB4?logo=php&logoColor=white)
[![Tests](https://github.com/revoltify/moneta/workflows/tests/badge.svg)](https://github.com/revoltify/moneta/actions)

---

## About Moneta

Moneta is a self-hosted money management app for small businesses and freelancers. Track income and expenses across wallets, watch budgets, and read clean financial reports — without accounting overhead. Single-user by design: your books, your server.

It ships with a built-in [MCP](https://modelcontextprotocol.io) server, so AI agents like Claude can record transactions and pull reports for you.

## Quick Start

```bash
docker run -d --name moneta \
  -p 8080:8080 \
  -v moneta-storage:/app/storage \
  ghcr.io/revoltify/moneta:latest
```

Open http://localhost:8080 and log in with `admin@admin.com` / `12345678` (change it in *Settings → Security*).

Prefer Compose? Grab [`compose.yaml`](compose.yaml) and run `docker compose up -d`. On shared hosting? See [shared hosting](#shared-hosting). No Docker? See [manual installation](#manual-installation).

## Features

- Dashboard with cash, income, expense, profit and cash-flow at a glance
- Income / expense / transfer / capital entry flows with edit and void
- Wallets with running-balance ledgers and one-click reconcile
- Budgets with alert thresholds
- Recurring transactions with catch-up
- Reports: Income Statement, Balance Sheet, Cash Flow, Category Breakdown, Monthly Summary
- Multiple companies side by side, each with its own currency and timezone
- AI agent access via MCP (26 tools) — OAuth or API tokens
- Audit log, ⌘K global search, dark mode, 2FA and passkeys
- Integer money storage (no float drift) + `moneta:verify-balances` integrity check

## Configuration

Everything works with zero configuration. For tweaks, see [`.env.example`](.env.example) — Docker takes the same variables as container environment, everyone else edits `.env` (the install wizard writes it for you).

## Shared Hosting

No SSH, Composer or Node needed — every release ships a prebuilt zip with vendor and frontend assets included.

1. Download `moneta-vX.Y.Z.zip` from the [latest release](https://github.com/revoltify/moneta/releases/latest)
2. Upload and extract it on your host, point your domain's document root at the `public/` directory
3. Open your site — the install wizard walks you through requirements, database setup (SQLite, MySQL, MariaDB, PostgreSQL or SQL Server), and your admin account

Add the scheduler in your hosting panel's cron section:

```
* * * * * php /path/to/moneta/artisan schedule:run >> /dev/null 2>&1
```

To upgrade, extract the new release over the old files — your `.env`, database and `storage/` state are never part of the zip.

## Manual Installation

Requires PHP 8.3+, Composer, Node 20+.

```bash
git clone https://github.com/revoltify/moneta.git && cd moneta

composer run setup           # deps, .env, keys, database, migrations, frontend build
php artisan moneta:install   # admin account + first company
```

Serve `public/` with your web server of choice ([Laravel Herd](https://herd.laravel.com), Valet, nginx + FPM, or `php artisan serve`), and add the scheduler to cron:

```
* * * * * php /path/to/moneta/artisan schedule:run >> /dev/null 2>&1
```

Same default login as Docker: `admin@admin.com` / `12345678`, or pass your own:

```bash
php artisan moneta:install --name="Jane" --email="jane@example.com" --password="strong-password" --company="Acme"
```

---

## Connect an AI agent (MCP)

Moneta exposes everything at `POST /mcp` — reports, transactions, wallets, budgets, recurring. Two ways in:

**OAuth (claude.ai / Claude Desktop connectors):** add your instance URL + `/mcp` as a connector and approve access in the browser. Discovery and client registration are automatic.

**Bearer token (Claude Code, scripts):** create a token under *Settings → API tokens*, then:

```bash
claude mcp add --transport http moneta https://your-instance.com/mcp \
  --header "Authorization: Bearer <token>"
```

Then just ask: *"record a 2,500 office rent expense from the Bank wallet"* or *"show me this month's income statement"*.

---

## FAQ

**I forgot my password.** There's no email-based reset (no mailer needed). From the server:

```bash
php artisan tinker --execute 'App\Models\User::first()->update(["password" => "new-password"]);'
```

**Can I try it with demo data?** `php artisan migrate:fresh --seed` creates a populated workspace — log in as `demo@example.com` / `password`.

**Can multiple people use it?** No — Moneta is intentionally single-user. One admin, multiple companies.

**Which databases are supported?** SQLite (default, zero-config), MySQL, MariaDB, PostgreSQL and SQL Server.

---

## Development

```bash
composer run dev       # app + hot-reloading frontend
composer test          # full gate: pint, phpstan, eslint, tsc, pest
php artisan test --compact --filter=Name   # single test
```

Issues and pull requests welcome — `composer test` must pass.

## License

[AGPL-3.0](LICENSE.md)
