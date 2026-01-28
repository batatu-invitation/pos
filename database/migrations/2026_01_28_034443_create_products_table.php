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
        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('sku')->unique();
            $table->foreignUuid('category_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('price', 10, 2);
            $table->integer('stock')->default(0);
            $table->string('status')->default('Active');
            $table->string('icon')->nullable();
            $table->string('tenant_id')->nullable();
            $table->foreignUuid('user_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
