<?php

use App\Models\User;
use App\Models\BalanceHistory;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

use function Livewire\Volt\layout;
use function Livewire\Volt\rules;
use function Livewire\Volt\state;

layout('layouts.guest');

state([
    'first_name' => '',
    'last_name' => '',
    'email' => '',
    'password' => '',
    'password_confirmation' => '',
    'terms' => false,
]);

rules([
    'first_name' => ['required', 'string', 'max:255'],
    'last_name' => ['required', 'string', 'max:255'],
    'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
    'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
    'terms' => ['accepted'],
]);

$register = function () {
    $validated = $this->validate();

    $userData = [
        'first_name' => $validated['first_name'],
        'last_name' => $validated['last_name'],
        'email' => $validated['email'],
        'password' => Hash::make($validated['password']),
        'role' => 'cashier', // Default for self-registration, admin can change later
        'status' => 'active',
        'balance' => 10000,
    ];

    DB::transaction(function () use ($userData) {
        event(new Registered($user = User::create($userData)));

        BalanceHistory::create([
            'user_id' => $user->id,
            'amount' => 10000,
            'type' => 'addition',
            'description' => 'Bonus Saldo Awal',
        ]);

        $user->assignRole('Manager');

        Auth::login($user);

        if (!session()->has('locale')) {
            session(['locale' => 'id']);
        }
    });

    $this->redirect(route('dashboard', absolute: false));
};

?>

<div class="h-screen flex overflow-hidden">
    <!-- Left Side - Image -->
    <div class="hidden lg:flex w-1/2 bg-indigo-900 justify-center items-center relative overflow-hidden">
        <div class="absolute inset-0 bg-indigo-900/90 z-10"></div>
         <img src="https://kimi-web-img.moonshot.cn/img/st3.depositphotos.com/4cc9e3a33ee5dfd8c68ce7b6f12dbc7298ee93aa.jpg" alt="POS Background" class="absolute inset-0 w-full h-full object-cover">
       
        <div class="relative z-20 text-white text-center p-12">
            <div class="mb-6 inline-flex items-center justify-center w-16 h-16 rounded-full bg-white/10 backdrop-blur-sm">
                <i class="fas fa-user-plus text-3xl"></i>
            </div>
            <h1 class="text-4xl font-bold mb-4">Join Us Today</h1>
            <p class="text-indigo-200 text-lg max-w-md mx-auto">Start managing your business efficiently with our POS system.</p>
        </div>
    </div>

    <!-- Right Side - Register Form -->
    <div class="w-full lg:w-1/2 flex items-center justify-center p-8 overflow-y-auto">
        <div class="max-w-md w-full">
            <div class="text-center mb-10">
                <h2 class="text-3xl font-bold text-gray-900">Create Account</h2>
                <p class="text-gray-500 mt-2">Get started with your free account.</p>
            </div>

            <form wire:submit="register" class="space-y-6">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="first-name" class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
                        <input wire:model="first_name" type="text" id="first-name" class="block w-full rounded-lg border-gray-300 border p-2.5 focus:ring-primary focus:border-primary sm:text-sm" placeholder="John" required autofocus>
                        <x-input-error :messages="$errors->get('first_name')" class="mt-2" />
                    </div>
                    <div>
                        <label for="last-name" class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                        <input wire:model="last_name" type="text" id="last-name" class="block w-full rounded-lg border-gray-300 border p-2.5 focus:ring-primary focus:border-primary sm:text-sm" placeholder="Doe" required>
                        <x-input-error :messages="$errors->get('last_name')" class="mt-2" />
                    </div>
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="far fa-envelope text-gray-400"></i>
                        </div>
                        <input wire:model="email" type="email" id="email" class="pl-10 block w-full rounded-lg border-gray-300 border p-2.5 focus:ring-primary focus:border-primary sm:text-sm" placeholder="john@example.com" required autocomplete="username">
                    </div>
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-lock text-gray-400"></i>
                        </div>
                        <input wire:model="password" type="password" id="password" class="pl-10 block w-full rounded-lg border-gray-300 border p-2.5 focus:ring-primary focus:border-primary sm:text-sm" placeholder="••••••••" required autocomplete="new-password">
                    </div>
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <div>
                    <label for="confirm-password" class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-lock text-gray-400"></i>
                        </div>
                        <input wire:model="password_confirmation" type="password" id="confirm-password" class="pl-10 block w-full rounded-lg border-gray-300 border p-2.5 focus:ring-primary focus:border-primary sm:text-sm" placeholder="••••••••" required autocomplete="new-password">
                    </div>
                    <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                </div>

                <div class="flex items-center">
                    <input wire:model="terms" id="terms" name="terms" type="checkbox" class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded">
                    <label for="terms" class="ml-2 block text-sm text-gray-900">I agree to the <a href="#" class="text-primary hover:text-indigo-500">Terms of Service</a> and <a href="#" class="text-primary hover:text-indigo-500">Privacy Policy</a></label>
                </div>
                <x-input-error :messages="$errors->get('terms')" class="mt-2" />

                <div>
                    <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-primary hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors">
                        Create Account
                    </button>
                </div>
            </form>

            <div class="mt-8 text-center">
                <p class="text-sm text-gray-600">
                    Already have an account?
                    <a href="{{ route('login') }}" class="font-medium text-primary hover:text-indigo-500" wire:navigate>Sign in</a>
                </p>
            </div>
        </div>
    </div>
</div>
