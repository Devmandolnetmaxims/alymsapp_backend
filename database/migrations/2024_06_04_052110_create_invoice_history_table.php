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
        Schema::create('invoice_history', function (Blueprint $table) {
            $table->id();
            $table->integer('invoice_id');
            $table->text('amount')->nullable();
            $table->text('balance')->nullable();
            $table->text('pay')->nullable();
            $table->timestamp('recived_at')->nullable();
            $table->enum('action',['1','2','3','4'])->nullable()->comment('1=Create, 2=Update, 3=Delete, 4=Pay');
            $table->text('report_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_history');
    }
};
