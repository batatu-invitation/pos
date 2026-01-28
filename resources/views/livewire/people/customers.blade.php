<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new
#[Layout('components.layouts.app')]
#[Title('Customers - Modern POS')]
class extends Component
{
    public $customers = [
        ['name' => 'John Doe', 'email' => 'john@example.com', 'phone' => '+1 234 567 890', 'total_spent' => '$1,200.00', 'last_visit' => '2 days ago', 'avatar' => 'https://ui-avatars.com/api/?name=John+Doe&background=random'],
        ['name' => 'Sarah Smith', 'email' => 'sarah@example.com', 'phone' => '+1 987 654 321', 'total_spent' => '$450.00', 'last_visit' => '1 week ago', 'avatar' => 'https://ui-avatars.com/api/?name=Sarah+Smith&background=random'],
        ['name' => 'Michael Brown', 'email' => 'michael.b@example.com', 'phone' => '+1 555 123 456', 'total_spent' => '$2,340.50', 'last_visit' => '3 days ago', 'avatar' => 'https://ui-avatars.com/api/?name=Michael+Brown&background=random'],
        ['name' => 'Emily Davis', 'email' => 'emily.d@example.com', 'phone' => '+1 555 987 654', 'total_spent' => '$890.00', 'last_visit' => '5 days ago', 'avatar' => 'https://ui-avatars.com/api/?name=Emily+Davis&background=random'],
        ['name' => 'David Wilson', 'email' => 'david.w@example.com', 'phone' => '+1 555 222 333', 'total_spent' => '$150.00', 'last_visit' => '2 weeks ago', 'avatar' => 'https://ui-avatars.com/api/?name=David+Wilson&background=random'],
        ['name' => 'Jessica Garcia', 'email' => 'jessica.g@example.com', 'phone' => '+1 555 444 555', 'total_spent' => '$3,120.75', 'last_visit' => '1 day ago', 'avatar' => 'https://ui-avatars.com/api/?name=Jessica+Garcia&background=random'],
        ['name' => 'James Martinez', 'email' => 'james.m@example.com', 'phone' => '+1 555 666 777', 'total_spent' => '$560.25', 'last_visit' => '3 weeks ago', 'avatar' => 'https://ui-avatars.com/api/?name=James+Martinez&background=random'],
        ['name' => 'Linda Rodriguez', 'email' => 'linda.r@example.com', 'phone' => '+1 555 888 999', 'total_spent' => '$2,890.00', 'last_visit' => '4 days ago', 'avatar' => 'https://ui-avatars.com/api/?name=Linda+Rodriguez&background=random'],
        ['name' => 'Robert Hernandez', 'email' => 'robert.h@example.com', 'phone' => '+1 555 111 222', 'total_spent' => '$1,780.50', 'last_visit' => '6 days ago', 'avatar' => 'https://ui-avatars.com/api/?name=Robert+Hernandez&background=random'],
        ['name' => 'Patricia Lopez', 'email' => 'patricia.l@example.com', 'phone' => '+1 555 333 444', 'total_spent' => '$95.00', 'last_visit' => '1 month ago', 'avatar' => 'https://ui-avatars.com/api/?name=Patricia+Lopez&background=random'],
        ['name' => 'William Gonzalez', 'email' => 'william.g@example.com', 'phone' => '+1 555 555 666', 'total_spent' => '$4,560.00', 'last_visit' => '2 days ago', 'avatar' => 'https://ui-avatars.com/api/?name=William+Gonzalez&background=random'],
        ['name' => 'Elizabeth Wilson', 'email' => 'elizabeth.w@example.com', 'phone' => '+1 555 777 888', 'total_spent' => '$1,120.00', 'last_visit' => '1 week ago', 'avatar' => 'https://ui-avatars.com/api/?name=Elizabeth+Wilson&background=random'],
    ];
}; ?>

<div class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Customers</h2>
        <a href="#" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">
            <i class="fas fa-plus mr-2"></i> Add Customer
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-600">
                <thead class="bg-gray-50 text-xs uppercase font-semibold text-gray-500">
                    <tr>
                        <th class="px-6 py-4">Name</th>
                        <th class="px-6 py-4">Email</th>
                        <th class="px-6 py-4">Phone</th>
                        <th class="px-6 py-4">Total Spent</th>
                        <th class="px-6 py-4">Last Visit</th>
                        <th class="px-6 py-4 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($customers as $customer)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 font-medium text-gray-800 flex items-center">
                            <img src="{{ $customer['avatar'] }}" class="w-8 h-8 rounded-full mr-3" alt="Avatar">
                            {{ $customer['name'] }}
                        </td>
                        <td class="px-6 py-4">{{ $customer['email'] }}</td>
                        <td class="px-6 py-4">{{ $customer['phone'] }}</td>
                        <td class="px-6 py-4 font-bold text-gray-800">{{ $customer['total_spent'] }}</td>
                        <td class="px-6 py-4">{{ $customer['last_visit'] }}</td>
                        <td class="px-6 py-4 text-right">
                            <a href="#" class="text-indigo-600 hover:text-indigo-900 mr-3"><i class="fas fa-eye"></i></a>
                            <button class="text-red-600 hover:text-red-900"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex items-center justify-between">
            <span class="text-sm text-gray-500">Showing {{ count($customers) }} of {{ count($customers) }} entries</span>
            <div class="flex space-x-2">
                <button class="px-3 py-1 border border-gray-300 rounded-md bg-white text-sm text-gray-500 hover:bg-gray-50 disabled:opacity-50" disabled>Previous</button>
                <button class="px-3 py-1 border border-gray-300 rounded-md bg-white text-sm text-gray-500 hover:bg-gray-50 disabled:opacity-50" disabled>Next</button>
            </div>
        </div>
    </div>
</div>
