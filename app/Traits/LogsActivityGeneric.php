<?php

namespace App\Traits;

use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

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
}
