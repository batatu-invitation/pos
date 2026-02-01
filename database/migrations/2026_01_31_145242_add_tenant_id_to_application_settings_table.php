<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('application_settings', function (Blueprint $table) {
             if (!Schema::hasColumn('application_settings', 'tenant_id')) {
                $table->foreignUuid('tenant_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
                $table->index(['tenant_id', 'key']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('application_settings', function (Blueprint $table) {
            $table->dropColumn('tenant_id');
        });
    }
};
