<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;

use function Livewire\Volt\form;
use function Livewire\Volt\layout;

layout('layouts.guest');

form(LoginForm::class);

$login = function () {
    $this->validate();

    $this->form->authenticate();

    Session::regenerate();

    if (!session()->has('locale')) {
            session(['locale' => 'id']);
    }

    $this->redirectIntended(default: route('dashboard', absolute: false));
};

?>

<div class="h-screen flex overflow-hidden">
    <!-- Left Side - Image/Branding -->
    <div class="hidden lg:flex w-1/2 bg-indigo-900 justify-center items-center relative overflow-hidden">
        <div class="absolute inset-0 bg-indigo-900/90 z-10"></div>
        <img src="https://images.unsplash.com/photo-1556742049-0cfed4f7a07d?ixlib=rb-4.0.3&auto=format&fit=crop&w=1470&q=80" alt="POS Background" class="absolute inset-0 w-full h-full object-cover">

        <div class="relative z-20 text-white text-center p-12">
            <div class="mb-6 inline-flex items-center justify-center w-16 h-16 rounded-full bg-white/10 backdrop-blur-sm">
                <i class="fas fa-cash-register text-3xl"></i>
            </div>
            <h1 class="text-4xl font-bold mb-4">Modern POS System</h1>
            <p class="text-indigo-200 text-lg max-w-md mx-auto">Manage your sales, inventory, and employees with our professional point of sale solution.</p>
        </div>
    </div>

    <!-- Right Side - Login Form -->
    <div class="w-full lg:w-1/2 flex items-center justify-center p-8">
        <div class="max-w-md w-full">
            <div class="text-center mb-10">
                <h2 class="text-3xl font-bold text-gray-900">Welcome Back</h2>
                <p class="text-gray-500 mt-2">Please enter your details to sign in.</p>
            </div>

            <!-- Session Status -->
            <x-auth-session-status class="mb-4" :status="session('status')" />

            <form wire:submit="login" class="space-y-6">
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="far fa-envelope text-gray-400"></i>
                        </div>
                        <input wire:model="form.email" type="email" id="email" class="pl-10 block w-full rounded-lg border-gray-300 border p-2.5 focus:ring-primary focus:border-primary sm:text-sm" placeholder="admin@example.com" required autofocus autocomplete="username">
                    </div>
                    <x-input-error :messages="$errors->get('form.email')" class="mt-2" />
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-lock text-gray-400"></i>
                        </div>
                        <input wire:model="form.password" type="password" id="password" class="pl-10 block w-full rounded-lg border-gray-300 border p-2.5 focus:ring-primary focus:border-primary sm:text-sm" placeholder="••••••••" required autocomplete="current-password">
                    </div>
                    <x-input-error :messages="$errors->get('form.password')" class="mt-2" />
                </div>

                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input wire:model="form.remember" id="remember-me" name="remember" type="checkbox" class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded">
                        <label for="remember-me" class="ml-2 block text-sm text-gray-900">Remember me</label>
                    </div>

                    <div class="text-sm">
                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="font-medium text-primary hover:text-indigo-500" wire:navigate>Forgot password?</a>
                        @endif
                    </div>
                </div>

                <div>
                    <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-primary hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors">
                        Sign In
                    </button>
                </div>
            </form>

            <div class="mt-8 text-center">
                <p class="text-sm text-gray-600">
                    Don't have an account?
                    <a href="{{ route('register') }}" class="font-medium text-primary hover:text-indigo-500" wire:navigate>Create an account</a>
                </p>
            </div>
        </div>
    </div>
</div>
