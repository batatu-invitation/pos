<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new
#[Layout('components.layouts.app')]
#[Title('Employees - Modern POS')]
class extends Component
{
    public $employees = [
        ['name' => 'John Doe', 'role' => 'Manager', 'email' => 'john.manager@pos.com', 'status' => 'Active', 'status_color' => 'green', 'avatar' => 'https://ui-avatars.com/api/?name=John+Doe&background=random'],
        ['name' => 'Alice Smith', 'role' => 'Cashier', 'email' => 'alice@pos.com', 'status' => 'Active', 'status_color' => 'green', 'avatar' => 'https://ui-avatars.com/api/?name=Alice+Smith&background=random'],
        ['name' => 'Bob Johnson', 'role' => 'Cashier', 'email' => 'bob@pos.com', 'status' => 'On Leave', 'status_color' => 'yellow', 'avatar' => 'https://ui-avatars.com/api/?name=Bob+Johnson&background=random'],
        ['name' => 'Emma Brown', 'role' => 'Supervisor', 'email' => 'emma.b@pos.com', 'status' => 'Active', 'status_color' => 'green', 'avatar' => 'https://ui-avatars.com/api/?name=Emma+Brown&background=random'],
        ['name' => 'Charlie Davis', 'role' => 'Stock Clerk', 'email' => 'charlie@pos.com', 'status' => 'Inactive', 'status_color' => 'red', 'avatar' => 'https://ui-avatars.com/api/?name=Charlie+Davis&background=random'],
        ['name' => 'Grace Wilson', 'role' => 'Cashier', 'email' => 'grace@pos.com', 'status' => 'Active', 'status_color' => 'green', 'avatar' => 'https://ui-avatars.com/api/?name=Grace+Wilson&background=random'],
        ['name' => 'Henry Moore', 'role' => 'Manager', 'email' => 'henry.m@pos.com', 'status' => 'Active', 'status_color' => 'green', 'avatar' => 'https://ui-avatars.com/api/?name=Henry+Moore&background=random'],
        ['name' => 'Isabella Taylor', 'role' => 'Cashier', 'email' => 'isabella@pos.com', 'status' => 'Active', 'status_color' => 'green', 'avatar' => 'https://ui-avatars.com/api/?name=Isabella+Taylor&background=random'],
        ['name' => 'Jack Anderson', 'role' => 'Stock Clerk', 'email' => 'jack@pos.com', 'status' => 'On Leave', 'status_color' => 'yellow', 'avatar' => 'https://ui-avatars.com/api/?name=Jack+Anderson&background=random'],
        ['name' => 'Kelly Thomas', 'role' => 'Supervisor', 'email' => 'kelly@pos.com', 'status' => 'Active', 'status_color' => 'green', 'avatar' => 'https://ui-avatars.com/api/?name=Kelly+Thomas&background=random'],
        ['name' => 'Liam Martinez', 'role' => 'Cashier', 'email' => 'liam@pos.com', 'status' => 'Inactive', 'status_color' => 'red', 'avatar' => 'https://ui-avatars.com/api/?name=Liam+Martinez&background=random'],
        ['name' => 'Mia Hernandez', 'role' => 'Manager', 'email' => 'mia.h@pos.com', 'status' => 'Active', 'status_color' => 'green', 'avatar' => 'https://ui-avatars.com/api/?name=Mia+Hernandez&background=random'],
    ];
}; ?>

<div class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Employees</h2>
        <button class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">
            <i class="fas fa-plus mr-2"></i> Add Employee
        </button>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-600">
                <thead class="bg-gray-50 text-xs uppercase font-semibold text-gray-500">
                    <tr>
                        <th class="px-6 py-4">Name</th>
                        <th class="px-6 py-4">Role</th>
                        <th class="px-6 py-4">Email</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($employees as $employee)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 flex items-center">
                            <img src="{{ $employee['avatar'] }}" class="w-8 h-8 rounded-full mr-3" alt="Avatar">
                            <span class="font-medium text-gray-800">{{ $employee['name'] }}</span>
                        </td>
                        <td class="px-6 py-4">{{ $employee['role'] }}</td>
                        <td class="px-6 py-4">{{ $employee['email'] }}</td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-{{ $employee['status_color'] }}-100 text-{{ $employee['status_color'] }}-800">
                                {{ $employee['status'] }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <button class="text-indigo-600 hover:text-indigo-900 mr-3"><i class="fas fa-edit"></i></button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex items-center justify-between">
            <span class="text-sm text-gray-500">Showing {{ count($employees) }} of {{ count($employees) }} entries</span>
            <div class="flex space-x-2">
                <button class="px-3 py-1 border border-gray-300 rounded-md bg-white text-sm text-gray-500 hover:bg-gray-50 disabled:opacity-50" disabled>Previous</button>
                <button class="px-3 py-1 border border-gray-300 rounded-md bg-white text-sm text-gray-500 hover:bg-gray-50 disabled:opacity-50" disabled>Next</button>
            </div>
        </div>
    </div>
</div>
