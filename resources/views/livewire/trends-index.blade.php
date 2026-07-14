<div>
    <div class="max-w-4xl mx-auto px-4 py-8">
        <h1 class="text-2xl font-bold mb-6">Trends em Tempo Real</h1>

        <div class="flex flex-wrap gap-4 mb-6">
            <div class="flex flex-col">
                <label for="period" class="text-sm font-medium text-gray-700 mb-1">Período</label>
                <select
                    id="period"
                    wire:model.live="period"
                    class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                    <option value="4h">Últimas 4 horas</option>
                    <option value="24h">Últimas 24 horas</option>
                    <option value="48h">Últimas 48 horas</option>
                    <option value="7d">Últimos 7 dias</option>
                </select>
            </div>

            <div class="flex flex-col">
                <label for="region" class="text-sm font-medium text-gray-700 mb-1">Região</label>
                <select
                    id="region"
                    wire:model.live="region"
                    class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                    <option value="">Todas</option>
                    @foreach ($regions as $r)
                        <option value="{{ $r->code }}">{{ $r->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex flex-col">
                <label for="category" class="text-sm font-medium text-gray-700 mb-1">Categoria</label>
                <select
                    id="category"
                    wire:model.live="category"
                    class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                    <option value="">Todas</option>
                    @foreach ($categories as $c)
                        <option value="{{ $c->slug }}">{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>

            <div
                wire:loading
                wire:target="period,region,category"
                class="flex items-end text-sm text-gray-500 pb-2"
            >
                <svg class="animate-spin h-4 w-4 mr-1 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                Carregando...
            </div>
        </div>

        <div class="bg-white shadow rounded-lg divide-y">
            @forelse ($trends as $trend)
                <div class="px-4 py-3 flex items-center gap-4">
                    <span class="text-lg font-bold text-gray-400 w-8 text-right shrink-0">
                        {{ $trend->rank }}
                    </span>

                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            @if ($trend->topArticle)
                                <a
                                    href="{{ $trend->topArticle->url }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="text-blue-600 hover:underline font-medium truncate"
                                >
                                    {{ $trend->term }}
                                </a>
                                <span class="text-xs text-gray-400 truncate">
                                    — {{ $trend->topArticle->site_name }}
                                </span>
                            @else
                                <span class="font-medium text-gray-800">{{ $trend->term }}</span>
                            @endif
                        </div>

                        @if ($trend->category)
                            <span class="inline-block mt-1 text-xs bg-gray-100 text-gray-600 rounded px-2 py-0.5">
                                {{ $trend->category->name }}
                            </span>
                        @endif
                    </div>
                </div>
            @empty
                <div class="px-4 py-8 text-center text-gray-500">
                    Nenhuma trend encontrada para os filtros selecionados.
                </div>
            @endforelse
        </div>

        <div class="mt-4">
            {{ $trends->links() }}
        </div>
    </div>
</div>
