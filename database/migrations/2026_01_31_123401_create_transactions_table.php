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
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('source_id')->index()->nullable();
            $table->foreignUuid('user_id')->index()->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('input_id')->index()->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('customer_id')->index()->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignUuid('tenant_id')->index()->nullable()->constrained('tenants')->nullOnDelete();
            $table->enum('type', ['income', 'expense']);
            $table->decimal('amount', 15, 2);
            $table->string('category')->nullable();
            $table->string('description')->nullable();
            $table->date('date');
            $table->string('reference_number')->nullable();
            $table->string('source_type')->index()->nullable();
            $table->string('payment_method')->nullable();
            $table->string('status')->default('completed');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
