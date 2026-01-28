<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTenantsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->string('id')->primary();

            // Custom columns for Branch management
            $table->string('name');
            $table->string('code')->unique();
            $table->string('type')->default('Retail Store');
            $table->string('initial', 2);
            $table->string('initial_color');
            $table->string('location');
            $table->string('manager');
            $table->string('phone');
            $table->string('email');
            $table->string('status')->default('Active');
            $table->string('status_color')->default('green');

            $table->timestamps();
            $table->softDeletes();
            $table->json('data')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
}
