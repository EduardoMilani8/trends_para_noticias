# TrendNews — contexto do projeto

## O que é
Site que mostra as pesquisas mais populares do Google Trends nas últimas horas,
cada uma associada à notícia/site mais relevante sobre o assunto. O usuário
filtra por região, período e categoria.

## Stack
- Laravel 12 + Livewire 4 no frontend (sem SPA separada, sem API JSON pública)
- PostgreSQL
- Deploy alvo: Render (web service + background worker + cron job nativo do Render)

## Fontes de dados externas
- SerpApi (Google Trends "Trending Now") — trending topics por região.
  Chave em SERPAPI_KEY no .env.
  **Atenção:** a API NÃO retorna campo `rank` — o ranking é a posição no array.
- Google News RSS (`https://news.google.com/rss/search?q=...`) — resolve o
  artigo/site mais popular pra cada termo. Não precisa de API key.
  **Atenção:** URLs do Google News podem ter mais de 255 caracteres —
  as colunas `url`, `title`, `site_name` de `trend_articles` são `text`.

## Modelo de dados
- `regions`: id, code (ISO country code, ex: BR, US), name
- `categories`: id, slug, name
- `trends`: id, term, normalized_term, region_id (FK), category_id (FK, nullable),
  period (string: 4h/24h/48h/7d), rank (int), search_volume (int, nullable),
  first_seen_at, last_seen_at, is_active (bool)
  - índice único em (normalized_term, region_id, period)
- `trend_articles`: id, trend_id (FK), url (text), site_name (text), title (text),
  published_at (nullable), position (int, 1 = principal), fetched_at

## Convenções
- Sem dependências externas reais em testes unitários — mocka os clients de
  API (SerpApi e Google News). Nada de HTTP de verdade em teste.
- Commits pequenos, um por etapa concluída.
- Sempre rodar `php artisan migrate` e `php artisan test` antes de considerar
  uma etapa concluída.
- Scheduling do job de coleta NÃO usa o Laravel Scheduler — quem dispara é o
  Cron Job nativo do Render, chamando `php artisan trends:collect` direto.
  Menos peça pra quebrar.

## Arquitetura da aplicação

### Componentes Livewire
- `TrendsIndex` (`app/Livewire/TrendsIndex.php`) — página inicial na rota `/`.
  Props com `#[Url]`: `$period` (default '24h'), `$region` (default ''), `$category` (default '').
  Consulta `Trend::active()` com eager loading de `region`, `category`, `topArticle`.
  Paginação de 15 itens com `WithPagination`.
  Layout via `#[Layout('layouts.app')]`.

### Layout Blade
- `resources/views/layouts/app.blade.php` — usa `{{ $slot }}` (não `@yield('content')`)
  porque o Livewire 3/4 injeta o componente via slot.
- Tailwind CSS via CDN (pra dev). Em produção será Vite.

### Jobs e Fila
- `ResolveTrendArticleJob` — recebe um `Trend` e `regionCode`, busca artigo
  no Google News RSS e cria `TrendArticle` com position=1.
  Roda na fila `database`, retry natural do Laravel.
- A fila precisa estar rodando (`php artisan queue:work`) pra resolver artigos.
  Sem worker = trends sem artigo na listagem.

### Comando artisan
- `trends:collect {--region=}` — coleta trends da SerpApi pra todas as regiões
  (ou uma específica). Cria/atualiza trends, despacha jobs de resolução de
  artigos, desativa trends que não voltaram da API.

## Armadilhas conhecidas
1. **SerpApi sem campo `rank`:** o código deriva rank do índice (`$i + 1`).
2. **URLs longas do Google News:** colunas de `trend_articles` são `text`, não `string`.
3. **Layout Livewire:** usar `{{ $slot }}`, não `@yield('content')`.
4. **Testes Feature:** precisam de `use RefreshDatabase` quando a rota consulta o banco.
5. **Fila parada:** sem `queue:work` rodando, artigos não são resolvidos.
6. **Jobs acumulam:** se a fila ficou muito tempo parada, pode ter muitos jobs
   presos. Limpar com `DB::table('jobs')->truncate()` e re-dispatch.

## Comandos úteis
```bash
composer dev                          # sobe tudo em paralelo
php artisan trends:collect --region=BR  # coleta dados do Brasil
php artisan queue:work --max-jobs=50  # processa 50 jobs e para
php artisan queue:work                # processa fila indefinidamente
php artisan test                      # roda suite de testes
```
