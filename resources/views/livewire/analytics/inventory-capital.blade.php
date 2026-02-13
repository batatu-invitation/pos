<div class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6 dark:bg-gray-900">
    <!-- Header Section -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 mb-8">
        <div>
            <h2 class="text-3xl font-bold text-gray-800 tracking-tight dark:text-gray-100">{{ __('Inventory Capital') }}</h2>
            <p class="text-gray-500 mt-2 text-sm dark:text-gray-400">{{ __('Overview of your total inventory value and assets.') }}</p>
        </div>
        <div class="flex items-center space-x-3">
             <div x-data="{ open: false }" class="relative">
                <button @click="open = !open" class="inline-flex items-center px-4 py-2.5 bg-white border border-gray-200 rounded-xl font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-700">
                    <i class="fas fa-file-export mr-2 text-gray-400"></i> {{ __('Export') }}
                    <i class="fas fa-chevron-down ml-2 text-xs text-gray-400"></i>
                </button>
                <div x-show="open" 
                     @click.away="open = false"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-75"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-95"
                     class="absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-lg z-50 py-2 border border-gray-100 dark:bg-gray-800 dark:border-gray-700" 
                     style="display: none;">
                    <button wire:click="exportPdf" @click="open = false" class="block w-full text-left px-4 py-2.5 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 transition-colors dark:text-gray-200 dark:hover:bg-gray-700">
                        <i class="fas fa-file-pdf mr-2 text-red-500"></i> {{ __('Export PDF') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Overview Bento -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
        <!-- Total Capital -->
        <div class="bg-gradient-to-br from-indigo-600 to-indigo-700 p-6 rounded-3xl shadow-lg shadow-indigo-200 text-white relative overflow-hidden group hover:scale-[1.02] transition-transform duration-300 dark:shadow-none">
            <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -mr-10 -mt-10 blur-2xl group-hover:blur-3xl transition-all duration-500"></div>
            <div class="relative z-10">
                <p class="text-indigo-100 font-medium mb-1">{{ __('Total Capital') }}</p>
                <h3 class="text-3xl font-bold">Rp {{ number_format($totalCapital, 0, ',', '.') }}</h3>
                <p class="text-indigo-100 text-sm mt-2 flex items-center">
                    <i class="fas fa-coins mr-1"></i> {{ __('Total value of stock') }}
                </p>
            </div>
            <div class="absolute bottom-4 right-4 text-indigo-400/30 text-5xl">
                <i class="fas fa-wallet"></i>
            </div>
        </div>
        
        <!-- Total Products -->
        <div class="bg-gradient-to-br from-emerald-500 to-teal-600 p-6 rounded-3xl shadow-lg shadow-emerald-200 text-white relative overflow-hidden group hover:scale-[1.02] transition-transform duration-300 dark:shadow-none">
            <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -mr-10 -mt-10 blur-2xl group-hover:blur-3xl transition-all duration-500"></div>
            <div class="relative z-10">
                <p class="text-emerald-100 font-medium mb-1">{{ __('Product Types') }}</p>
                <h3 class="text-3xl font-bold">{{ number_format($totalProducts) }}</h3>
                <p class="text-emerald-100 text-sm mt-2 flex items-center">
                    <i class="fas fa-box mr-1"></i> {{ __('Distinct items') }}
                </p>
            </div>
            <div class="absolute bottom-4 right-4 text-emerald-400/30 text-5xl">
                <i class="fas fa-boxes"></i>
            </div>
        </div>

        <!-- Total Stock Items -->
        <div class="bg-gradient-to-br from-blue-500 to-cyan-600 p-6 rounded-3xl shadow-lg shadow-blue-200 text-white relative overflow-hidden group hover:scale-[1.02] transition-transform duration-300 dark:shadow-none">
            <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -mr-10 -mt-10 blur-2xl group-hover:blur-3xl transition-all duration-500"></div>
            <div class="relative z-10">
                <p class="text-blue-100 font-medium mb-1">{{ __('Total Stock Items') }}</p>
                <h3 class="text-3xl font-bold">{{ number_format($totalItems) }}</h3>
                <p class="text-blue-100 text-sm mt-2 flex items-center">
                    <i class="fas fa-layer-group mr-1"></i> {{ __('Total quantity') }}
                </p>
            </div>
            <div class="absolute bottom-4 right-4 text-blue-400/30 text-5xl">
                <i class="fas fa-cubes"></i>
            </div>
        </div>
    </div>

    <!-- Data Table -->
    <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden dark:bg-gray-800 dark:border-gray-700">
        <div class="p-6 border-b border-gray-100 flex flex-col md:flex-row md:items-center justify-between gap-4 dark:border-gray-700">
            <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100">{{ __('Inventory Details') }}</h3>
            
            <div class="w-full md:w-72 relative">
                 <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fas fa-search text-gray-400"></i>
                </div>
                <input wire:model.live.debounce.300ms="search" type="text" class="block w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-xl leading-5 bg-gray-50 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 dark:placeholder-gray-500" placeholder="{{ __('Search products...') }}">
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider cursor-pointer hover:text-indigo-600 transition-colors dark:text-gray-400" wire:click="sortBy('name')">
                            {{ __('Product') }}
                            @if($sortField === 'name') <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ml-1"></i> @endif
                        </th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider cursor-pointer hover:text-indigo-600 transition-colors dark:text-gray-400" wire:click="sortBy('sku')">
                            {{ __('SKU') }}
                            @if($sortField === 'sku') <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ml-1"></i> @endif
                        </th>
                        <th scope="col" class="px-6 py-4 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider cursor-pointer hover:text-indigo-600 transition-colors dark:text-gray-400" wire:click="sortBy('stock')">
                            {{ __('Stock') }}
                            @if($sortField === 'stock') <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ml-1"></i> @endif
                        </th>
                        <th scope="col" class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider cursor-pointer hover:text-indigo-600 transition-colors dark:text-gray-400" wire:click="sortBy('cost')">
                            {{ __('Cost') }}
                            @if($sortField === 'cost') <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ml-1"></i> @endif
                        </th>
                        <th scope="col" class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider cursor-pointer hover:text-indigo-600 transition-colors dark:text-gray-400" wire:click="sortBy('price')">
                            {{ __('Price') }}
                            @if($sortField === 'price') <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ml-1"></i> @endif
                        </th>
                        <th scope="col" class="px-6 py-4 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider cursor-pointer hover:text-indigo-600 transition-colors dark:text-gray-400" wire:click="sortBy('margin')">
                            {{ __('Margin') }}
                            @if($sortField === 'margin') <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ml-1"></i> @endif
                        </th>
                        <th scope="col" class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider dark:text-gray-400">
                            {{ __('Total Capital') }}
                        </th>
                        <th scope="col" class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider dark:text-gray-400">
                            {{ __('Total Margin') }}
                        </th>
                    </tr>
                </thead>

                <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                    @forelse($products as $product)
                        <tr class="hover:bg-gray-50 transition-colors dark:hover:bg-gray-700/30">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="h-10 w-10 flex-shrink-0">
                                        @if($product->image)
                                            <img class="h-10 w-10 rounded-lg object-cover" src="{{ Storage::url($product->image) }}" alt="">
                                        @else
                                            <div class="h-10 w-10 rounded-lg bg-gray-100 flex items-center justify-center text-xl dark:bg-gray-700">
                                                {{ $product->emoji->icon ?? '📦' }}
                                            </div>
                                        @endif
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $product->name }}</div>
                                        <div class="text-xs text-indigo-500 dark:text-indigo-400">{{ $product->category->name ?? 'Uncategorized' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 font-mono dark:text-gray-400">
                                {{ $product->sku }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span class="px-2.5 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $product->stock > 10 ? 'bg-green-100 text-green-800' : ($product->stock > 0 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                    {{ number_format($product->stock) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right dark:text-gray-300">
                                Rp {{ number_format($product->cost, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right dark:text-gray-300">
                                Rp {{ number_format($product->price, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium text-green-600 dark:text-green-400">
                                Rp {{ number_format($product->margin, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 text-right dark:text-white">
                                Rp {{ number_format($product->cost * $product->stock, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 text-right dark:text-white">
                                Rp {{ number_format($product->margin * $product->stock, 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-10 text-center text-gray-500 dark:text-gray-400">
                                <p>{{ __('No products found.') }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>

                <tfoot class="bg-gray-100 dark:bg-gray-700/80 border-t-2 border-gray-200 dark:border-gray-600">
                    <tr>
                        <td colspan="2" class="px-6 py-4 text-sm font-black text-gray-900 dark:text-white text-right uppercase tracking-wider">
                            Grand Total
                        </td>
                        <td class="px-6 py-4 text-center text-sm font-bold text-gray-900 dark:text-white bg-gray-200/50 dark:bg-gray-800/50">
                            {{ number_format($products->sum('stock'), 0, ',', '.') }}
                        </td>
                        <td colspan="3">
                           
                        </td>
                        <td class="px-6 py-4 text-right text-sm font-bold text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/20">
                            Rp {{ number_format($products->sum(fn($p) => $p->cost * $p->stock), 0, ',', '.') }}
                        </td>
                        <td class="px-6 py-4 text-right text-sm font-bold text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/20">
                            Rp {{ number_format($products->sum(fn($p) => $p->margin * $p->stock), 0, ',', '.') }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
