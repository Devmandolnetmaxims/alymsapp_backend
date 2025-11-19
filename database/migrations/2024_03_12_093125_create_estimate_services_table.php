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
        Schema::create('estimate_services', function (Blueprint $table) {
            $table->id();
            $table->integer('service_id')->nullable();
            $table->text('temp_service')->nullable();
            $table->integer('estimate_id');
            $table->integer('quantity')->nullable();
            $table->float('discount')->nullable();
            $table->text('description')->nullable();
            $table->integer('rate')->nullable();
            $table->integer('total')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estimate_services');
    }
};
