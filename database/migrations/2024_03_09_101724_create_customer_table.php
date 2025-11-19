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
        Schema::create('customer', function (Blueprint $table) {
            $table->id();
            $table->text('first_name');
            $table->text('last_name' )->nullable();
            $table->text('phone' )->nullable();
            $table->text('email')->nullable();
            $table->text('trade_rate')->nullable();
            $table->text('company_name' )->nullable();
            $table->text('company_type' )->nullable();
            $table->text('street')->nullable();
            $table->text('area')->nullable();
            $table->text('town')->nullable();
            $table->text('post_code')->nullable();
            $table->text('work_phone')->nullable();
            $table->text('insurance_company')->nullable();
            $table->text('policy_number')->nullable();
            $table->integer('due_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer');
    }
};
