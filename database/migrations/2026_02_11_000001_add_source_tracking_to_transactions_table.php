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
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('source_type')->nullable()->after('reference_number');
            $table->uuid('source_id')->nullable()->after('source_type');
            $table->foreignUuid('user_id')->nullable()->after('source_id')->constrained('users')->nullOnDelete();
            $table->foreignUuid('customer_id')->nullable()->after('user_id')->constrained('customers')->nullOnDelete();
            $table->foreignUuid('tenant_id')->nullable()->after('customer_id')->constrained('tenants')->nullOnDelete();
            
            // Add index for efficient querying
            $table->index(['source_type', 'source_id']);
            $table->index('user_id');
            $table->index('customer_id');
            $table->index('tenant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['source_type', 'source_id']);
            $table->dropIndex(['user_id']);
            $table->dropIndex(['customer_id']);
            $table->dropIndex(['tenant_id']);
            
            $table->dropForeign(['user_id']);
            $table->dropForeign(['customer_id']);
            $table->dropForeign(['tenant_id']);
            
            $table->dropColumn(['source_type', 'source_id', 'user_id', 'customer_id', 'tenant_id']);
        });
    }
};