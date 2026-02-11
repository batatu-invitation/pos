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
        $tables = [
            'transactions',
            'balance_histories',
            'journal_entries',
            'accounts',
            'purchases',
            'colors',
            'emojis', // noted in products table migration as emojis
            'application_settings',
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) {
                    if (!Schema::hasColumn($table->getTable(), 'input_id')) {
                        $table->foreignUuid('input_id')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
                    }
                });
            }
        }

        // Handle suppliers which might be missing both
        if (Schema::hasTable('suppliers')) {
            Schema::table('suppliers', function (Blueprint $table) {
                if (!Schema::hasColumn('suppliers', 'user_id')) {
                    $table->foreignUuid('user_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
                }
                if (!Schema::hasColumn('suppliers', 'input_id')) {
                    $table->foreignUuid('input_id')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'transactions',
            'balance_histories',
            'journal_entries',
            'accounts',
            'purchases',
            'colors',
            'emojis',
            'application_settings',
            'suppliers',
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    if (Schema::hasColumn($tableName, 'input_id')) {
                        $table->dropForeign([$tableName . '_input_id_foreign']);
                        $table->dropColumn('input_id');
                    }
                    if ($tableName === 'suppliers' && Schema::hasColumn($tableName, 'user_id')) {
                        $table->dropForeign([$tableName . '_user_id_foreign']);
                        $table->dropColumn('user_id');
                    }
                });
            }
        }
    }
};
