<?php

namespace App\Traits;

use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Models\Activity;

trait LogsActivityGeneric
{
    use LogsActivity;

    /**
     * Konfigurasi otomatis untuk Spatie Activitylog
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()               // Mencatat semua kolom yang ada di tabel
            ->logOnlyDirty()         // Hanya mencatat kolom yang isinya berubah
            ->dontSubmitEmptyLogs()  // Jangan simpan log jika tidak ada perubahan data
            ->useLogName(str_replace('_', ' ', $this->getTable())); // Nama log sesuai nama tabel
    }

    /**
     * Tap into the activity before it is saved.
     */
    public function tapActivity(Activity $activity, string $eventName)
    {
        $properties = $activity->properties ?? collect();
        $activity->properties = $properties->merge([
            'ip' => request()->ip(),
            'device' => request()->userAgent(),
        ]);
    }
}
