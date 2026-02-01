<div class="w-full max-w-[900px] h-[600px] flex relative z-10 bg-[#1e1e1e]/80 backdrop-blur-2xl rounded-xl overflow-hidden shadow-2xl border border-white/10 ring-1 ring-black/50 font-sans selection:bg-blue-500/30">
    <!-- Sidebar (Left Side) - macOS Style -->
    <div class="hidden md:flex w-[260px] bg-[#282828]/90 border-r border-white/10 flex-col relative">
        <!-- Window Controls -->
        <div class="h-12 flex items-center px-4 gap-2 absolute top-0 left-0 w-full z-20">
            <div class="w-3 h-3 rounded-full bg-[#FF5F57] border border-[#E0443E]/50"></div>
            <div class="w-3 h-3 rounded-full bg-[#FEBC2E] border border-[#D89E24]/50"></div>
            <div class="w-3 h-3 rounded-full bg-[#28C840] border border-[#1AAB29]/50"></div>
        </div>

        <div class="mt-16 px-4 flex-1 overflow-y-auto">
            <div class="flex items-center gap-3 mb-8 px-2 opacity-90">
                 <!-- Icon -->
                <div class="w-7 h-7 bg-gradient-to-br from-blue-500 to-blue-600 rounded-md flex items-center justify-center text-white shadow-inner border border-white/10">
                   <i class="fas fa-cube text-xs"></i>
                </div>
                <span class="font-medium text-white tracking-wide text-[13px]">{{ __('Setup Assistant') }}</span>
            </div>
            
            <nav class="space-y-1">
                <!-- Step 1 -->
                <div class="px-3 py-1.5 rounded-md text-[13px] font-medium transition-all flex items-center gap-3
                    {{ $step === 1 ? 'bg-[#007AFF] text-white shadow-sm' : 'text-white/60 hover:text-white' }}">
                    @if($step > 1) <i class="fas fa-check-circle text-blue-400 text-xs"></i> @else <i class="far fa-circle text-xs {{ $step === 1 ? 'text-white' : 'text-white/30' }}"></i> @endif
                    <span>{{ __('Welcome') }}</span>
                </div>

                <!-- Step 2 -->
                 <div class="px-3 py-1.5 rounded-md text-[13px] font-medium transition-all flex items-center gap-3
                    {{ $step === 2 ? 'bg-[#007AFF] text-white shadow-sm' : 'text-white/60 hover:text-white' }}">
                     @if($step > 2) <i class="fas fa-check-circle text-blue-400 text-xs"></i> @else <i class="far fa-circle text-xs {{ $step === 2 ? 'text-white' : 'text-white/30' }}"></i> @endif
                    <span>{{ __('Store Info') }}</span>
                </div>

                <!-- Step 3 -->
                 <div class="px-3 py-1.5 rounded-md text-[13px] font-medium transition-all flex items-center gap-3
                    {{ $step === 3 ? 'bg-[#007AFF] text-white shadow-sm' : 'text-white/60 hover:text-white' }}">
                     @if($step > 3) <i class="fas fa-check-circle text-blue-400 text-xs"></i> @else <i class="far fa-circle text-xs {{ $step === 3 ? 'text-white' : 'text-white/30' }}"></i> @endif
                    <span>{{ __('Location') }}</span>
                </div>

                <!-- Step 4 -->
                 <div class="px-3 py-1.5 rounded-md text-[13px] font-medium transition-all flex items-center gap-3
                    {{ $step === 4 ? 'bg-[#007AFF] text-white shadow-sm' : 'text-white/60 hover:text-white' }}">
                     @if($step > 4) <i class="fas fa-check-circle text-blue-400 text-xs"></i> @else <i class="far fa-circle text-xs {{ $step === 4 ? 'text-white' : 'text-white/30' }}"></i> @endif
                    <span>{{ __('Receipt') }}</span>
                </div>
            </nav>
        </div>
        
        <div class="p-4 text-[10px] text-white/30 text-center border-t border-white/5">
            Inovasi Bung v1.0
        </div>
    </div>

    <!-- Main Content (Right Side) -->
    <div class="flex-1 flex flex-col bg-[#1e1e1e]/50 relative">
        <div class="flex-1 overflow-y-auto p-10 flex flex-col items-center justify-center text-center">
            
            <!-- Step 1: Welcome -->
            @if($step === 1)
                <div class="animate-fade-in max-w-md mx-auto flex flex-col items-center">
                    <div class="w-24 h-24 bg-gradient-to-b from-[#007AFF] to-[#0055B3] rounded-full shadow-2xl mb-8 flex items-center justify-center border-4 border-[#2c2c2c]">
                         <i class="fas fa-rocket text-4xl text-white drop-shadow-md transform -rotate-12"></i>
                    </div>
                    
                    <h1 class="text-3xl font-light text-white mb-3 tracking-tight">Welcome to Inovasi Bung</h1>
                    <p class="text-[14px] text-white/60 leading-relaxed max-w-[320px]">
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
                            <input wire:model="storeName" type="text" class="w-full bg-[#2a2a2a] border border-white/10 rounded-[6px] px-3 py-2 text-[13px] text-white placeholder-white/30 focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 focus:border-[#007AFF] transition-all shadow-sm" placeholder="Store Name">
                            @error('storeName') <span class="text-red-400 text-[11px] ml-1 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <input wire:model="email" type="email" class="w-full bg-[#2a2a2a] border border-white/10 rounded-[6px] px-3 py-2 text-[13px] text-white placeholder-white/30 focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 focus:border-[#007AFF] transition-all shadow-sm" placeholder="Email Address">
                             @error('email') <span class="text-red-400 text-[11px] ml-1 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                         <div>
                            <input wire:model="phone" type="text" class="w-full bg-[#2a2a2a] border border-white/10 rounded-[6px] px-3 py-2 text-[13px] text-white placeholder-white/30 focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 focus:border-[#007AFF] transition-all shadow-sm" placeholder="Phone Number">
                             @error('phone') <span class="text-red-400 text-[11px] ml-1 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                             <div>
                                <select wire:model="currency" class="w-full bg-[#2a2a2a] border border-white/10 rounded-[6px] px-3 py-2 text-[13px] text-white focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 focus:border-[#007AFF] transition-all appearance-none shadow-sm">
                                    <option value="" class="text-white/30">Currency</option>
                                    @foreach($currencies as $code => $name)
                                        <option value="{{ $code }}">{{ $code }}</option>
                                    @endforeach
                                </select>
                                 @error('currency') <span class="text-red-400 text-[11px] ml-1 mt-1 block">{{ $message }}</span> @enderror
                            </div>
                            
                            <div>
                                <select wire:model="timezone" class="w-full bg-[#2a2a2a] border border-white/10 rounded-[6px] px-3 py-2 text-[13px] text-white focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 focus:border-[#007AFF] transition-all appearance-none shadow-sm">
                                    <option value="" class="text-white/30">Timezone</option>
                                    @foreach($timezones as $offset => $name)
                                        <option value="{{ $offset }}">{{ $offset }}</option>
                                    @endforeach
                                </select>
                                 @error('timezone') <span class="text-red-400 text-[11px] ml-1 mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Step 3: Address -->
            @if($step === 3)
                <div class="animate-fade-in w-full max-w-[360px] text-left">
                    <div class="text-center mb-8">
                        <h2 class="text-2xl font-light text-white mb-1">{{ __('Location') }}</h2>
                        <p class="text-[13px] text-white/50">{{ __('Where can customers find you?') }}</p>
                    </div>
                    
                    <div class="space-y-4">
                        <div>
                            <textarea wire:model="streetAddress" rows="3" class="w-full bg-[#2a2a2a] border border-white/10 rounded-[6px] px-3 py-2 text-[13px] text-white placeholder-white/30 focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 focus:border-[#007AFF] transition-all resize-none shadow-sm" placeholder="{{ __('Street Address') }}"></textarea>
                             @error('streetAddress') <span class="text-red-400 text-[11px] ml-1 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <input wire:model="city" type="text" class="w-full bg-[#2a2a2a] border border-white/10 rounded-[6px] px-3 py-2 text-[13px] text-white placeholder-white/30 focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 focus:border-[#007AFF] transition-all shadow-sm" placeholder="{{ __('City') }}">
                                 @error('city') <span class="text-red-400 text-[11px] ml-1 mt-1 block">{{ $message }}</span> @enderror
                            </div>
                            
                            <div>
                                <input wire:model="zipCode" type="text" class="w-full bg-[#2a2a2a] border border-white/10 rounded-[6px] px-3 py-2 text-[13px] text-white placeholder-white/30 focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 focus:border-[#007AFF] transition-all shadow-sm" placeholder="{{ __('Zip Code') }}">
                                 @error('zipCode') <span class="text-red-400 text-[11px] ml-1 mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Step 4: Receipt -->
            @if($step === 4)
                <div class="animate-fade-in w-full max-w-[360px] text-left">
                    <div class="text-center mb-8">
                        <h2 class="text-2xl font-light text-white mb-1">{{ __('Receipt Settings') }}</h2>
                        <p class="text-[13px] text-white/50">{{ __('Customize your printed receipts.') }}</p>
                    </div>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-[11px] font-medium text-white/50 mb-1">{{ __('Receipt Header') }}</label>
                            <textarea wire:model="receiptHeader" rows="3" class="w-full bg-[#2a2a2a] border border-white/10 rounded-[6px] px-3 py-2 text-[13px] text-white placeholder-white/30 focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 focus:border-[#007AFF] transition-all resize-none shadow-sm" placeholder="{{ __('Company Name, Address, etc.') }}"></textarea>
                            @error('receiptHeader') <span class="text-red-400 text-[11px] ml-1 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-[11px] font-medium text-white/50 mb-1">{{ __('Receipt Footer') }}</label>
                            <textarea wire:model="receiptFooter" rows="3" class="w-full bg-[#2a2a2a] border border-white/10 rounded-[6px] px-3 py-2 text-[13px] text-white placeholder-white/30 focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 focus:border-[#007AFF] transition-all resize-none shadow-sm" placeholder="{{ __('Thank you message, etc.') }}"></textarea>
                            @error('receiptFooter') <span class="text-red-400 text-[11px] ml-1 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                         <div class="flex items-center gap-3 bg-[#2a2a2a] border border-white/10 rounded-[6px] px-3 py-2">
                            <input id="show-logo" type="checkbox" wire:model.live="receiptShowLogo" class="rounded border-white/10 bg-[#3a3a3a] text-[#007AFF] focus:ring-[#007AFF]/50">
                            <label for="show-logo" class="text-[13px] text-white select-none">{{ __('Show Logo on Receipt') }}</label>
                        </div>

                        @if($receiptShowLogo)
                            <div class="mt-7">
                                <label class="block text-[11px] font-medium text-white/50 mb-2">{{ __('Upload Logo') }}</label>
                                <div class="flex items-center justify-center w-full">
                                    <label for="logo-upload" class="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed border-white/10 rounded-lg cursor-pointer bg-[#2a2a2a] hover:bg-[#333] hover:border-[#007AFF]/50 transition-all relative overflow-hidden group">
                                        @if ($logo)
                                            <img src="{{ $logo->temporaryUrl() }}" class="absolute inset-0 w-full h-full object-contain p-2 z-10">
                                            <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity z-20 flex items-center justify-center">
                                                 <p class="text-xs text-white">{{ __('Change') }}</p>
                                            </div>
                                        @else
                                            <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                                <i class="fas fa-cloud-upload-alt text-2xl text-white/30 mb-2 group-hover:text-[#007AFF] transition-colors"></i>
                                                <p class="mb-1 text-[11px] text-white/50"><span class="font-medium text-white/70">{{ __('Click to upload') }}</span></p>
                                                <p class="text-[10px] text-white/30">{{ __('PNG, JPG (MAX. 1MB)') }}</p>
                                            </div>
                                        @endif
                                        <input id="logo-upload" type="file" wire:model="logo" class="hidden" accept="image/*" />
                                    </label>
                                </div>
                                @error('logo') <span class="text-red-400 text-[11px] ml-1 mt-1 block">{{ $message }}</span> @enderror
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
        <div class="h-[60px] border-t border-white/10 flex items-center justify-between px-6 bg-[#282828]/95 backdrop-blur-md absolute bottom-0 w-full rounded-b-xl z-20">
             <div>
                @if($step > 1)
                    <button wire:click="prevStep" class="text-[13px] text-white/70 hover:text-white font-medium transition-colors flex items-center gap-2 px-2 py-1 rounded hover:bg-white/5">
                        <i class="fas fa-chevron-left text-[10px]"></i> {{ __('Back') }}
                    </button>
                @else
                    <!-- Placeholder to balance flex -->
                    <div class="w-10"></div>
                @endif
            </div>

            <div>
                @if($step < 4)
                    <button wire:click="nextStep" class="bg-[#007AFF] hover:bg-[#0062CC] text-white text-[13px] font-medium px-5 py-1 rounded-full shadow-sm transition-all active:scale-95 border-t border-white/10">
                        {{ __('Continue') }}
                    </button>
                @else
                    <button wire:click="finish" class="bg-[#007AFF] hover:bg-[#0062CC] text-white text-[13px] font-medium px-5 py-1 rounded-full shadow-sm transition-all active:scale-95 flex items-center gap-2 border-t border-white/10">
                        {{ __('Finish') }}
                    </button>
                @endif
            </div>
        </div>
    </div>
</div>
