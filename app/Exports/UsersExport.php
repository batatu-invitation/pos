<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UsersExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $search;
    protected $roleFilter;

    public function __construct($search = '', $roleFilter = '')
    {
        $this->search = $search;
        $this->roleFilter = $roleFilter;
    }

    public function collection()
    {
        return User::query()
            ->when($this->search, fn($q) => $q->where(function($sub) {
                $sub->where('first_name', 'like', '%'.$this->search.'%')
                    ->orWhere('last_name', 'like', '%'.$this->search.'%')
                    ->orWhere('email', 'like', '%'.$this->search.'%');
            }))
            ->when($this->roleFilter && $this->roleFilter !== 'All Roles', fn($q) => $q->role($this->roleFilter))
            ->latest()
            ->get();
    }

    public function headings(): array
    {
        return [
            'Full Name',
            'Email',
            'Phone',
            'Role',
            'Status',
            'Created At',
        ];
    }

    public function map($user): array
    {
        return [
            $user->name, // using the accessor
            $user->email,
            $user->phone,
            $user->roles->first()?->name ?? $user->role,
            $user->status,
            $user->created_at->format('d/m/Y H:i'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
