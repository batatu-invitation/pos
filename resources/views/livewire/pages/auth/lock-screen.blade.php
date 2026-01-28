<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

use function Livewire\Volt\layout;
use function Livewire\Volt\rules;
use function Livewire\Volt\state;

layout('layouts.guest');

state(['password' => '']);

rules(['password' => ['required', 'string']]);

$unlock = function () {
    $this->validate();

    if (! Auth::guard('web')->validate([
        'email' => Auth::user()->email,
        'password' => $this->password,
    ])) {
        throw ValidationException::withMessages([
            'password' => __('The provided password does not match our records.'),
        ]);
    }

    $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
};

?>

<div class="h-screen flex items-center justify-center p-4 bg-gray-900">
    <div class="max-w-md w-full text-center">
        <div class="mb-8">
            <img src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&size=128&background=4f46e5&color=fff" class="w-32 h-32 rounded-full mx-auto border-4 border-white shadow-lg" alt="Avatar">
            <h2 class="text-2xl font-bold text-white mt-4">{{ Auth::user()->name }}</h2>
            <p class="text-gray-400">Enter your password to unlock</p>
        </div>

        <form wire:submit="unlock" class="max-w-xs mx-auto space-y-4">
            <div class="relative">
                <input wire:model="password" type="password" class="w-full px-4 py-3 rounded-full bg-gray-800 border border-gray-700 text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-center placeholder-gray-500" placeholder="Password" required autofocus>
                <button type="submit" class="absolute right-2 top-1.5 p-1.5 bg-indigo-600 rounded-full text-white hover:bg-indigo-700 transition-colors w-9 h-9 flex items-center justify-center">
                    <i class="fas fa-arrow-right"></i>
                </button>
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </form>
        
        <div class="mt-8">
            <form method="POST" action="{{ route('logout') }}" class="inline">
                @csrf
                <button type="submit" class="text-gray-400 hover:text-white text-sm">
                    Or sign in as a different user
                </button>
            </form>
        </div>
    </div>
</div>
