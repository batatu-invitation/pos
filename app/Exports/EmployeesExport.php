<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EmployeesExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    public function collection()
    {
        $user = auth()->user();
        if ($user->hasRole('Super Admin')) {
            return User::with('roles')
                ->where('id', '!=', $user->id)
                ->latest()
                ->get();
        }

        return User::where('created_by', $user->id)
            ->with('roles')
            ->latest()
            ->get();
    }

    public function headings(): array
    {
        return [
            'Name',
            'Email',
            'Phone',
            'Role',
            'Status',
            'Created At',
        ];
    }

    public function map($employee): array
    {
        return [
            $employee->first_name . ' ' . $employee->last_name,
            $employee->email,
            $employee->phone,
            $employee->roles->first()?->name ?? $employee->role,
            $employee->status,
            $employee->created_at->format('d/m/Y H:i'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
