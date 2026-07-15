# TrendNews

Pesquisas mais populares do Google Trends associadas à notícia mais relevante,
filtráveis por região, período e categoria.

## Stack

- **Backend:** Laravel 12 + Livewire 4 (PHP 8.2+)
- **Frontend:** Tailwind CSS v4 via CDN (será migrado pra Vite em produção)
- **Banco:** PostgreSQL
- **Fila:** database (Laravel jobs)
- **Deploy:** Render (blueprint via `render.yaml`)

## Como rodar no local

### Pré-requisitos

- PHP 8.2+
- Composer
- Node.js + npm
- PostgreSQL rodando localmente

### 1. Configurar o banco

Crie o banco e o usuário no PostgreSQL:

```sql
CREATE USER noticias WITH PASSWORD 'postgres';
CREATE DATABASE trendnews OWNER noticias;
```

### 2. Instalar dependências e configurar

```bash
composer setup    # instala deps PHP, gera APP_KEY, migra, npm install, npm build
```

### 3. Configurar a API key

No `.env`, preencha:

```
SERPAPI_KEY=sua_chave_aqui
```

A chave é gratuita (250 buscas/mês) e se obtém em https://serpapi.com/users/sign_up?plan=free

### 4. Rodar a aplicação

```bash
composer dev      # sobe serve + queue worker + pail + vite em paralelo
```

Isso inicia:
- **Servidor:** http://localhost:8000
- **Queue worker:** processa jobs de resolução de artigos
- **Vite:** compila Tailwind em hot-reload
- **Pail:** mostra logs em tempo real

### 5. Popular dados

```bash
php artisan trends:collect --region=BR
```

Coleta as trends do Google Trends pra Brasil e despacha jobs pra resolver
os artigos via Google News RSS. Repita pra outras regiões (US, PT, AR).

### Troubleshooting

| Problema | Solução |
|---|---|
| Tela branca no localhost | Verificar se `resources/views/layouts/app.blade.php` usa `{{ $slot }}` (não `@yield('content')`) |
| Jobs falhando na fila | Verificar se as colunas `url`, `title`, `site_name` de `trend_articles` são `text` (não `varchar(255)`) |
| `Undefined array key "rank"` | A SerpApi não retorna campo `rank` — o código já derivado do índice do array |
| `no such table: trends` nos testes | Faltou `use RefreshDatabase` no teste Feature |

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
> espera. Gere localmente com `php artisan key:generate --show` e configure
> manualmente no painel do Render.

### Build & Release

O `startCommand` de cada serviço roda `php artisan migrate --force` antes de
iniciar, garantindo que o banco esteja atualizado antes de receber tráfego.

### Cron job

O `trends-cron` usa o **cron nativo do Render** (não o Laravel Scheduler).
Isso mantém uma peça a menos: o Render dispara `php artisan trends:collect`
a cada 3 horas direto, sem depender do `schedule:work` do Laravel.

## Estrutura do projeto

```
app/
  Livewire/           # Componentes Livewire
    TrendsIndex.php   # Página inicial com filtros
  Models/             # Trend, TrendArticle, Region, Category
  Services/
    Trends/           # SerpApiTrendsClient + FakeTrendsClient
    News/             # GoogleNewsResolver + FakeNewsResolver
  Jobs/               # ResolveTrendArticleJob
  Console/Commands/   # trends:collect
resources/views/
  layouts/app.blade.php       # Layout principal (Tailwind CDN)
  livewire/trends-index.blade.php  # View do componente
```

## Próximas atualizações

### Filtros (prioridade alta)
- **Filtrar por regiões reais:** atualmente só tem dados do Brasil, mas as
  regiões seedadas são BR, US, PT, AR. Coletar dados pra todas as regiões
  e garantir que o filtro funcione corretamente com múltiplas.
- **Melhorar UX dos filtros:** adicionar indicador visual de qual filtro
  está ativo, possível resetar filtros, e estados vazios mais informativos.
- **Filtro por período mais intuitivo:** considerar mostrar a data/hora
  da coleta ao lado do período selecionado.

### Artigos das trends (prioridade alta)
- **Resolver artigos pra todas as trends:** atualmente o `queue:work`
  precisa estar rodando pra resolver os artigos. Muitas trends ficam sem
  artigo se o worker não estiver ativo.
- **Mostrar notícias na listagem:** exibir o título e site do artigo
  principal de cada trend como link clicável (já implementado na view,
  mas depende dos artigos estarem resolvidos na fila).
- **Retry automático de jobs falhos:** implementar retry com backoff
  pra quando o Google News RSS falha temporariamente.

### Frontend
- **Migrar de Tailwind CDN pra Vite:** o CDN é pra dev. Em produção,
  usar o build do Vite (`npm run build`) pra assets otimizados.
- **Design responsivo:** a view atual é funcional mas precisa de
  tratamento visual pra mobile.
- **Paginação via URL:** usar `#[Url]` nas props do Livewire pra que
  a paginação e filtros sejam compartilháveis via URL.

### Infraestrutura
- **Health check endpoint:** adicionar rota `/up` pra o Render verificar
  se o serviço está saudável.
- **Rate limiting na SerpApi:** monitorar uso da API free (250 buscas/mês)
  e implementar cache local pra evitar requests duplicados.
