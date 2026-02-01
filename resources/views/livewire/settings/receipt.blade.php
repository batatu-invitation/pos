<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\ApplicationSetting;
use Livewire\WithFileUploads;
use Buglinjo\LaravelWebp\Webp;
use Illuminate\Support\Facades\Storage;

new #[Layout('components.layouts.app')]
#[Title('Receipt Settings - Modern POS')]
class extends Component
{
    use WithFileUploads;

    public $header;
    public $footer;
    public $showLogo;
    public $logo;
    public $existingLogo;

    public function mount()
    {
        $settings = ApplicationSetting::pluck('value', 'key');
        $this->header = $settings['receipt_header'] ?? "Modern POS\n123 Main St, New York\nTel: +1 234 567 890";
        $this->footer = $settings['receipt_footer'] ?? "Thank you for shopping with us!\nPlease come again.";
        $this->showLogo = filter_var($settings['receipt_show_logo'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $this->existingLogo = $settings['receipt_logo_path'] ?? null;
    }

    public function save()
    {
        $this->validate([
            'logo' => 'nullable|image|max:1024', // 1MB Max
        ]);

        if ($this->logo) {
            // Delete old logo if exists
            if ($this->existingLogo && Storage::disk('public')->exists($this->existingLogo)) {
                Storage::disk('public')->delete($this->existingLogo);
            }

            $filename = md5($this->logo->getClientOriginalName() . time()) . '.webp';
            $path = storage_path('app/public/receipt-logos');
            
            if (!file_exists($path)) {
                mkdir($path, 0755, true);
            }

            Webp::make($this->logo)->save($path . '/' . $filename);
            $logoPath = 'receipt-logos/' . $filename;
            
            ApplicationSetting::updateOrCreate(['key' => 'receipt_logo_path'], ['value' => $logoPath]);
            $this->existingLogo = $logoPath;
        }

        ApplicationSetting::updateOrCreate(['key' => 'receipt_header'], ['value' => $this->header]);
        ApplicationSetting::updateOrCreate(['key' => 'receipt_footer'], ['value' => $this->footer]);
        ApplicationSetting::updateOrCreate(['key' => 'receipt_show_logo'], ['value' => $this->showLogo]);

        session()->flash('message', 'Receipt settings saved successfully.');
        $this->dispatch('notify', __('Receipt settings saved successfully.'));
    }
}; ?>

<div class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6" x-data="{
        cropper: null,
        cropping: false,
        imageToCrop: null,
        initCropper() {
            if (this.cropper) {
                this.cropper.destroy();
            }
            this.cropper = new Cropper(document.getElementById('image-to-crop'), {
                aspectRatio: 1,
                viewMode: 1,
                autoCropArea: 1,
            });
        },
        startCropping(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.imageToCrop = e.target.result;
                    this.cropping = true;
                    this.$nextTick(() => {
                        this.initCropper();
                    });
                };
                reader.readAsDataURL(file);
            }
        },
        saveCrop() {
            if (this.cropper) {
                this.cropper.getCroppedCanvas().toBlob((blob) => {
                    const file = new File([blob], 'avatar.webp', { type: 'image/webp' });
                    @this.upload('logo', file, (uploadedFilename) => {
                        this.cropping = false;
                        this.cropper.destroy();
                        this.imageToCrop = null;
                    }, () => {
                        // Error callback
                    }, (event) => {
                        // Progress callback
                    });
                }, 'image/webp');
            }
        },
        cancelCrop() {
            this.cropping = false;
            if (this.cropper) {
                this.cropper.destroy();
            }
            this.imageToCrop = null;
            document.getElementById('logo-upload').value = '';
        }
    }">
    <!-- Cropping Modal -->
    <div x-show="cropping" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                {{ __('Crop Image') }}
                            </h3>
                            <div class="mt-2">
                                <div class="img-container w-full h-64">
                                    <img id="image-to-crop" :src="imageToCrop" class="max-w-full h-full block">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" @click="saveCrop" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm">
                        {{ __('Crop & Upload') }}
                    </button>
                    <button type="button" @click="cancelCrop" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        {{ __('Cancel') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-gray-800">{{ __('Settings') }}</h2>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-6">
        <div class="p-6">
            <form wire:submit="save" class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Receipt Header') }}</label>
                    <textarea wire:model="header" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5" rows="3"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Receipt Footer') }}</label>
                    <textarea wire:model="footer" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5" rows="3"></textarea>
                </div>

                <div class="flex items-center">
                    <input id="show-logo" type="checkbox" wire:model.live="showLogo" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                    <label for="show-logo" class="ml-2 block text-sm text-gray-900">{{ __('Show Logo on Receipt') }}</label>
                </div>

                @if($showLogo)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('Upload Logo') }}</label>
                        <div class="flex items-center justify-center w-full">
                            <label for="logo-upload" class="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100 transition-all relative overflow-hidden group">
                                @if ($logo)
                                    <img src="{{ $logo->temporaryUrl() }}" class="absolute inset-0 w-full h-full object-contain p-2 z-10">
                                    <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity z-20 flex items-center justify-center">
                                         <p class="text-xs text-white">{{ __('Change') }}</p>
                                    </div>
                                @elseif($existingLogo)
                                    <img src="{{ Storage::url($existingLogo) }}" class="absolute inset-0 w-full h-full object-contain p-2 z-10">
                                    <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity z-20 flex items-center justify-center">
                                         <p class="text-xs text-white">{{ __('Change') }}</p>
                                    </div>
                                @else
                                    <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                        <i class="fas fa-cloud-upload-alt text-2xl text-gray-400 mb-2 group-hover:text-indigo-600 transition-colors"></i>
                                        <p class="mb-1 text-xs text-gray-500"><span class="font-medium text-gray-700">{{ __('Click to upload') }}</span></p>
                                        <p class="text-[10px] text-gray-400">{{ __('PNG, JPG (MAX. 1MB)') }}</p>
                                    </div>
                                @endif
                                <input id="logo-upload" type="file" @change="startCropping" class="hidden" accept="image/*" />
                            </label>
                        </div>
                        @error('logo') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                @endif

                <div class="pt-4 flex justify-end">
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors"><i class="fas fa-save mr-2"></i> {{ __('Save Changes') }}</button>
                </div>
            </form>
        </div>
    </div>

   
</div>
