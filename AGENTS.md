# TrendNews — contexto do projeto

## O que é
Site que mostra as pesquisas mais populares do Google Trends nas últimas horas,
cada uma associada à notícia/site mais relevante sobre o assunto. O usuário
filtra por região, período e categoria.

## Stack
- Laravel (PHP) + Livewire 3 no frontend (sem SPA separada, sem API JSON pública)
- PostgreSQL
- Deploy alvo: Render (web service + background worker + cron job nativo do Render)

## Fontes de dados externas
- SerpApi (Google Trends "Trending Now") — trending topics por região.
  Chave em SERPAPI_KEY no .env.
- Google News RSS (`https://news.google.com/rss/search?q=...`) — resolve o
  artigo/site mais popular pra cada termo. Não precisa de API key.

## Modelo de dados
- `regions`: id, code (ISO country code, ex: BR, US), name
- `categories`: id, slug, name
- `trends`: id, term, normalized_term, region_id (FK), category_id (FK, nullable),
  period (string: 4h/24h/48h/7d), rank (int), search_volume (int, nullable),
  first_seen_at, last_seen_at, is_active (bool)
  - índice único em (normalized_term, region_id, period)
- `trend_articles`: id, trend_id (FK), url, site_name, title,
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