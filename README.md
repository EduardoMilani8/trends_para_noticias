# TrendNews

Pesquisas mais populares do Google Trends associadas à notícia mais relevante,
filtráveis por região, período e categoria.

## Stack

- **Backend:** Laravel 12 + Livewire 3 (PHP 8.2+)
- **Frontend:** Tailwind CSS v4 via Vite
- **Banco:** PostgreSQL
- **Fila:** database (Laravel jobs)
- **Deploy:** Render (blueprint via `render.yaml`)

## Setup local

```bash
composer setup    # instala deps, gera APP_KEY, migra, builda assets
composer dev      # serve + queue + pail + vite simultâneos
```

Variáveis de ambiente necessárias (ver `.env.example`):

| Variável | Descrição |
|---|---|
| `SERPAPI_KEY` | Chave da API SerpApi (Google Trends) |
| `DB_*` | Credenciais PostgreSQL |
| `QUEUE_CONNECTION=database` | Fila via tabela `jobs` no banco |

## Deploy no Render

### Método: Render Blueprint (render.yaml)

O deploy usa **Render Blueprints** — um `render.yaml` na raiz do repo define
todos os serviços. O Render provisiona tudo automaticamente ao importar o repo.

**Não usamos Dockerfile.** O Render suporta PHP como runtime nativo (`env: php`):
ele detecta a versão do PHP pelo `composer.json`, sobe Nginx + PHP-FPM, roda
`composer install`, e inicia a aplicação. Isso elimina a necessidade de
gerenciar containers manualmente — menos infraestrutura pra manter.

### Serviços criados pelo render.yaml

| Serviço | Tipo | O que faz |
|---|---|---|
| `trends-web` | web | Laravel via `artisan serve` (Nginx + PHP-FPM do Render) |
| `trends-worker` | worker | `php artisan queue:work --tries=3` |
| `trends-cron` | cron | `php artisan trends:collect` a cada 3 horas |
| `trends-db` | database | PostgreSQL gerenciado (plano free) |

### Variáveis de ambiente no Render

Defina no painel do Render (ou via Blueprint):

| Variável | Valor |
|---|---|
| `APP_KEY` | Gerar com `php artisan key:generate --show` e colar no painel |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `SERPAPI_KEY` | Chave da SerpApi |
| `QUEUE_CONNECTION` | `database` |
| `SESSION_DRIVER` | `database` |
| `CACHE_STORE` | `database` |
| `DATABASE_URL` | Gerenciado automaticamente pelo Render ao vincular `trends-db` |

> **`APP_KEY`:** o Blueprint define `sync: false` para essa variável, pois o
> `generateValue` do Render não gera chaves no formato base64 que o Laravel
> espera. Gere localmente com `php artisan key:build --show` e configure
> manualmente no painel do Render.

### Build & Release

O `startCommand` de cada serviço roda `php artisan migrate --force` antes de
iniciar, garantindo que o banco esteja atualizado antes de receber tráfego.

### Cron job

O `trends-cron` usa o **cron nativo do Render** (não o Laravel Scheduler).
Isso mantém uma peça a menos: o Render dispara `php artisan trends:collect`
a cada 3 horas direto, sem depender do `schedule:work` do Laravel.
