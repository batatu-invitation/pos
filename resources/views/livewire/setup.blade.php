<div class="w-full max-w-[900px] h-[600px] flex relative z-10 bg-white/80 dark:bg-[#1e1e1e]/80 backdrop-blur-2xl rounded-3xl overflow-hidden shadow-2xl border border-gray-200/50 dark:border-white/10 ring-1 ring-black/50 font-sans selection:bg-blue-500/30">
    <!-- Sidebar (Left Side) - Bento Grid Style -->
    <div class="hidden md:flex w-[260px] bg-gradient-to-br from-gray-50 to-gray-100 dark:from-[#282828]/90 dark:to-[#323232]/90 border-r border-gray-200/50 dark:border-white/10 flex-col relative rounded-l-3xl">
        <!-- Window Controls -->
        <div class="h-12 flex items-center px-4 gap-2 absolute top-0 left-0 w-full z-20">
            <div class="w-3 h-3 rounded-full bg-red-400 dark:bg-[#FF5F57] border border-red-500/50 dark:border-[#E0443E]/50"></div>
            <div class="w-3 h-3 rounded-full bg-yellow-400 dark:bg-[#FEBC2E] border border-yellow-500/50 dark:border-[#D89E24]/50"></div>
            <div class="w-3 h-3 rounded-full bg-green-400 dark:bg-[#28C840] border border-green-500/50 dark:border-[#1AAB29]/50"></div>
        </div>

        <div class="mt-16 px-4 flex-1 overflow-y-auto">
            <div class="flex items-center gap-3 mb-8 px-2 opacity-90">
                 <!-- Icon -->
                <div class="w-7 h-7 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-indigo-200/50 border border-white/10 dark:shadow-none">
                   <i class="fas fa-cube text-xs"></i>
                </div>
                <span class="font-medium text-gray-800 dark:text-white tracking-wide text-[13px]">{{ __('Setup Assistant') }}</span>
            </div>
            
            <nav class="space-y-1">
                <!-- Step 1 -->
                <div class="px-3 py-1.5 rounded-xl text-[13px] font-medium transition-all flex items-center gap-3
                    {{ $step === 1 ? 'bg-gradient-to-r from-indigo-500 to-purple-600 text-white shadow-lg shadow-indigo-200/50' : 'text-gray-600 hover:text-gray-800 dark:text-white/60 dark:hover:text-white' }}">
                    @if($step > 1) <i class="fas fa-check-circle text-indigo-400 text-xs"></i> @else <i class="far fa-circle text-xs {{ $step === 1 ? 'text-white' : 'text-gray-400 dark:text-white/30' }}"></i> @endif
                    <span>{{ __('Welcome') }}</span>
                </div>

                <!-- Step 2 -->
                 <div class="px-3 py-1.5 rounded-xl text-[13px] font-medium transition-all flex items-center gap-3
                    {{ $step === 2 ? 'bg-gradient-to-r from-indigo-500 to-purple-600 text-white shadow-lg shadow-indigo-200/50' : 'text-gray-600 hover:text-gray-800 dark:text-white/60 dark:hover:text-white' }}">
                     @if($step > 2) <i class="fas fa-check-circle text-indigo-400 text-xs"></i> @else <i class="far fa-circle text-xs {{ $step === 2 ? 'text-white' : 'text-gray-400 dark:text-white/30' }}"></i> @endif
                    <span>{{ __('Store Info') }}</span>
                </div>

                <!-- Step 3 -->
                 <div class="px-3 py-1.5 rounded-xl text-[13px] font-medium transition-all flex items-center gap-3
                    {{ $step === 3 ? 'bg-gradient-to-r from-indigo-500 to-purple-600 text-white shadow-lg shadow-indigo-200/50' : 'text-gray-600 hover:text-gray-800 dark:text-white/60 dark:hover:text-white' }}">
                     @if($step > 3) <i class="fas fa-check-circle text-indigo-400 text-xs"></i> @else <i class="far fa-circle text-xs {{ $step === 3 ? 'text-white' : 'text-gray-400 dark:text-white/30' }}"></i> @endif
                    <span>{{ __('Location') }}</span>
                </div>

                <!-- Step 4 -->
                 <div class="px-3 py-1.5 rounded-xl text-[13px] font-medium transition-all flex items-center gap-3
                    {{ $step === 4 ? 'bg-gradient-to-r from-indigo-500 to-purple-600 text-white shadow-lg shadow-indigo-200/50' : 'text-gray-600 hover:text-gray-800 dark:text-white/60 dark:hover:text-white' }}">
                     @if($step > 4) <i class="fas fa-check-circle text-indigo-400 text-xs"></i> @else <i class="far fa-circle text-xs {{ $step === 4 ? 'text-white' : 'text-gray-400 dark:text-white/30' }}"></i> @endif
                    <span>{{ __('Receipt') }}</span>
                </div>
            </nav>
        </div>
        
        <div class="p-4 text-[10px] text-gray-500 dark:text-white/30 text-center border-t border-gray-200/50 dark:border-white/5">
            Inovasi Bung v1.0
        </div>
    </div>

    <!-- Main Content (Right Side) -->
    <div class="flex-1 flex flex-col bg-gradient-to-br from-gray-50 to-gray-100 dark:from-[#1e1e1e]/50 dark:to-[#252525]/50 relative rounded-r-3xl">
        <div class="flex-1 overflow-y-auto p-10 flex flex-col items-center justify-center text-center">
            
            <!-- Step 1: Welcome -->
            @if($step === 1)
                <div class="animate-fade-in max-w-md mx-auto flex flex-col items-center">
                    <div class="w-24 h-24 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-full shadow-2xl mb-8 flex items-center justify-center border-4 border-white dark:border-[#2c2c2c]">
                         <i class="fas fa-rocket text-4xl text-white drop-shadow-md transform -rotate-12"></i>
                    </div>
                    
                    <h1 class="text-3xl font-light text-gray-800 dark:text-white mb-3 tracking-tight">Welcome to Inovasi Bung</h1>
                    <p class="text-[14px] text-gray-600 dark:text-white/60 leading-relaxed max-w-[320px]">
                        Let's get your store configured. Follow the steps to set up your basic information and preferences.
                    </p>
                </div>
            @endif

            <!-- Step 2: Store Info -->
            @if($step === 2)
                 <div class="animate-fade-in w-full max-w-[360px] text-left">
                    <div class="text-center mb-8">
                        <h2 class="text-2xl font-light text-white mb-1">Store Details</h2>
                        <p class="text-[13px] text-white/50">Enter your business information.</p>
                    </div>
                    
                    <div class="space-y-4">
                        <div>
                            <input wire:model="storeName" type="text" class="w-full bg-white dark:bg-[#2a2a2a] border border-gray-300 dark:border-white/10 rounded-xl px-4 py-3 text-sm text-gray-800 dark:text-white placeholder-gray-500 dark:placeholder-white/30 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all shadow-sm" placeholder="Store Name">
                            @error('storeName') <span class="text-red-500 dark:text-red-400 text-xs ml-1 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <input wire:model="email" type="email" class="w-full bg-white dark:bg-[#2a2a2a] border border-gray-300 dark:border-white/10 rounded-xl px-4 py-3 text-sm text-gray-800 dark:text-white placeholder-gray-500 dark:placeholder-white/30 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all shadow-sm" placeholder="Email Address">
                             @error('email') <span class="text-red-500 dark:text-red-400 text-xs ml-1 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                         <div>
                            <input wire:model="phone" type="text" class="w-full bg-white dark:bg-[#2a2a2a] border border-gray-300 dark:border-white/10 rounded-xl px-4 py-3 text-sm text-gray-800 dark:text-white placeholder-gray-500 dark:placeholder-white/30 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all shadow-sm" placeholder="Phone Number">
                             @error('phone') <span class="text-red-500 dark:text-red-400 text-xs ml-1 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                             <div>
                                <select wire:model="currency" class="w-full bg-white dark:bg-[#2a2a2a] border border-gray-300 dark:border-white/10 rounded-xl px-4 py-3 text-sm text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all appearance-none shadow-sm">
                                    <option value="" class="text-gray-400 dark:text-white/30">Currency</option>
                                    @foreach($currencies as $code => $name)
                                        <option value="{{ $code }}">{{ $code }}</option>
                                    @endforeach
                                </select>
                                 @error('currency') <span class="text-red-500 dark:text-red-400 text-xs ml-1 mt-1 block">{{ $message }}</span> @enderror
                            </div>
                            
                            <div>
                                <select wire:model="timezone" class="w-full bg-white dark:bg-[#2a2a2a] border border-gray-300 dark:border-white/10 rounded-xl px-4 py-3 text-sm text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all appearance-none shadow-sm">
                                    <option value="" class="text-gray-400 dark:text-white/30">Timezone</option>
                                    @foreach($timezones as $offset => $name)
                                        <option value="{{ $offset }}">{{ $offset }}</option>
                                    @endforeach
                                </select>
                                 @error('timezone') <span class="text-red-500 dark:text-red-400 text-xs ml-1 mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Step 3: Address -->
            @if($step === 3)
                <div class="animate-fade-in w-full max-w-[360px] text-left">
                    <div class="text-center mb-8">
                        <h2 class="text-2xl font-light text-gray-800 dark:text-white mb-1">{{ __('Location') }}</h2>
                        <p class="text-[13px] text-gray-600 dark:text-white/50">{{ __('Where can customers find you?') }}</p>
                    </div>
                    
                    <div class="space-y-4">
                        <div>
                            <textarea wire:model="streetAddress" rows="3" class="w-full bg-white dark:bg-[#2a2a2a] border border-gray-300 dark:border-white/10 rounded-xl px-4 py-3 text-sm text-gray-800 dark:text-white placeholder-gray-500 dark:placeholder-white/30 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all resize-none shadow-sm" placeholder="{{ __('Street Address') }}"></textarea>
                             @error('streetAddress') <span class="text-red-500 dark:text-red-400 text-xs ml-1 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <input wire:model="city" type="text" class="w-full bg-white dark:bg-[#2a2a2a] border border-gray-300 dark:border-white/10 rounded-xl px-4 py-3 text-sm text-gray-800 dark:text-white placeholder-gray-500 dark:placeholder-white/30 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all shadow-sm" placeholder="{{ __('City') }}">
                                 @error('city') <span class="text-red-500 dark:text-red-400 text-xs ml-1 mt-1 block">{{ $message }}</span> @enderror
                            </div>
                            
                            <div>
                                <input wire:model="zipCode" type="text" class="w-full bg-white dark:bg-[#2a2a2a] border border-gray-300 dark:border-white/10 rounded-xl px-4 py-3 text-sm text-gray-800 dark:text-white placeholder-gray-500 dark:placeholder-white/30 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all shadow-sm" placeholder="{{ __('Zip Code') }}">
                                 @error('zipCode') <span class="text-red-500 dark:text-red-400 text-xs ml-1 mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Step 4: Receipt -->
            @if($step === 4)
                <div class="animate-fade-in w-full max-w-[360px] text-left">
                    <div class="text-center mb-8">
                        <h2 class="text-2xl font-light text-gray-800 dark:text-white mb-1">{{ __('Receipt Settings') }}</h2>
                        <p class="text-[13px] text-gray-600 dark:text-white/50">{{ __('Customize your printed receipts.') }}</p>
                    </div>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-[11px] font-medium text-gray-600 dark:text-white/50 mb-1">{{ __('Receipt Header') }}</label>
                            <textarea wire:model="receiptHeader" rows="3" class="w-full bg-white dark:bg-[#2a2a2a] border border-gray-300 dark:border-white/10 rounded-xl px-4 py-3 text-sm text-gray-800 dark:text-white placeholder-gray-500 dark:placeholder-white/30 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all resize-none shadow-sm" placeholder="{{ __('Company Name, Address, etc.') }}"></textarea>
                            @error('receiptHeader') <span class="text-red-500 dark:text-red-400 text-xs ml-1 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-[11px] font-medium text-gray-600 dark:text-white/50 mb-1">{{ __('Receipt Footer') }}</label>
                            <textarea wire:model="receiptFooter" rows="3" class="w-full bg-white dark:bg-[#2a2a2a] border border-gray-300 dark:border-white/10 rounded-xl px-4 py-3 text-sm text-gray-800 dark:text-white placeholder-gray-500 dark:placeholder-white/30 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all resize-none shadow-sm" placeholder="{{ __('Thank you message, etc.') }}"></textarea>
                            @error('receiptFooter') <span class="text-red-500 dark:text-red-400 text-xs ml-1 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                         <div class="flex items-center gap-3 bg-white dark:bg-[#2a2a2a] border border-gray-300 dark:border-white/10 rounded-xl px-4 py-3 shadow-sm">
                            <input id="show-logo" type="checkbox" wire:model.live="receiptShowLogo" class="rounded border-gray-300 dark:border-white/10 bg-white dark:bg-[#3a3a3a] text-indigo-600 focus:ring-indigo-500/50">
                            <label for="show-logo" class="text-sm text-gray-800 dark:text-white select-none">{{ __('Show Logo on Receipt') }}</label>
                        </div>

                        @if($receiptShowLogo)
                            <div class="mt-7">
                                <label class="block text-[11px] font-medium text-gray-600 dark:text-white/50 mb-2">{{ __('Upload Logo') }}</label>
                                <div class="flex items-center justify-center w-full">
                                    <label for="logo-upload" class="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed border-gray-300 dark:border-white/10 rounded-xl cursor-pointer bg-white dark:bg-[#2a2a2a] hover:bg-gray-50 dark:hover:bg-[#333] hover:border-indigo-500/50 transition-all relative overflow-hidden group">
                                        @if ($logo)
                                            <img src="{{ $logo->temporaryUrl() }}" class="absolute inset-0 w-full h-full object-contain p-2 z-10">
                                            <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity z-20 flex items-center justify-center">
                                                 <p class="text-xs text-white">{{ __('Change') }}</p>
                                            </div>
                                        @else
                                            <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                                <i class="fas fa-cloud-upload-alt text-2xl text-gray-400 dark:text-white/30 mb-2 group-hover:text-indigo-500 transition-colors"></i>
                                                <p class="mb-1 text-[11px] text-gray-600 dark:text-white/50"><span class="font-medium text-gray-700 dark:text-white/70">{{ __('Click to upload') }}</span></p>
                                                <p class="text-[10px] text-gray-500 dark:text-white/30">{{ __('PNG, JPG (MAX. 1MB)') }}</p>
                                            </div>
                                        @endif
                                        <input id="logo-upload" type="file" wire:model="logo" class="hidden" accept="image/*" />
                                    </label>
                                </div>
                                @error('logo') <span class="text-red-500 dark:text-red-400 text-xs ml-1 mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        @endif
                        <br>
                        <br>
                        <br>
                    </div>
                </div>
            @endif

        </div>

        <!-- Navigation Bar (Bottom) -->
        <div class="h-[60px] border-t border-gray-200/50 dark:border-white/10 flex items-center justify-between px-6 bg-white/95 dark:bg-[#282828]/95 backdrop-blur-md absolute bottom-0 w-full rounded-b-3xl z-20">
             <div>
                @if($step > 1)
                    <button wire:click="prevStep" class="text-sm text-gray-600 dark:text-white/70 hover:text-gray-800 dark:text-white font-medium transition-colors flex items-center gap-2 px-3 py-2 rounded-xl hover:bg-gray-100 dark:hover:bg-white/5">
                        <i class="fas fa-chevron-left text-xs"></i> {{ __('Back') }}
                    </button>
                @else
                    <!-- Placeholder to balance flex -->
                    <div class="w-10"></div>
                @endif
            </div>

            <div>
                @if($step < 4)
                    <button wire:click="nextStep" class="bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white text-sm font-medium px-5 py-2 rounded-xl shadow-lg shadow-indigo-200/50 transition-all active:scale-95 border-t border-white/10 dark:shadow-none">
                        {{ __('Continue') }}
                    </button>
                @else
                    <button wire:click="finish" class="bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white text-sm font-medium px-5 py-2 rounded-xl shadow-lg shadow-indigo-200/50 transition-all active:scale-95 flex items-center gap-2 border-t border-white/10 dark:shadow-none">
                        {{ __('Finish') }}
                    </button>
                @endif
            </div>
        </div>
    </div>
</div>
