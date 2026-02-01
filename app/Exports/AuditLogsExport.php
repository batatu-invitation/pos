<?php

namespace App\Exports;

use Spatie\Activitylog\Models\Activity;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AuditLogsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $search;
    protected $actionFilter;

    public function __construct($search = '', $actionFilter = '')
    {
        $this->search = $search;
        $this->actionFilter = $actionFilter;
    }

    public function collection()
    {
        $query = Activity::with(['causer', 'subject'])->latest();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('description', 'like', '%' . $this->search . '%')
                  ->orWhere('event', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->actionFilter && $this->actionFilter !== 'All Actions') {
            $filter = strtolower($this->actionFilter);
            if ($filter === 'update') $filter = 'updated';
            if ($filter === 'create') $filter = 'created';
            if ($filter === 'delete') $filter = 'deleted';

            $query->where('event', $filter);
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Description',
            'Event',
            'Causer',
            'Subject Type',
            'Subject ID',
            'Properties',
            'Created At',
        ];
    }

    public function map($activity): array
    {
        return [
            $activity->id,
            $activity->description,
            $activity->event,
            $activity->causer ? $activity->causer->name : 'System',
            $activity->subject_type,
            $activity->subject_id,
            json_encode($activity->properties),
            $activity->created_at->format('Y-m-d H:i:s'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
