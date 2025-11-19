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
        Schema::create('estimates', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->text('registration')->nullable();
            $table->integer('module')->nullable();
            $table->integer('make_id')->nullable();
            $table->integer('model_id')->nullable();
            $table->text('paint_code')->nullable();
            $table->text('description')->nullable();
            $table->text('files')->nullable();
            $table->enum('status', ['Approved', 'Pending', 'Draft', 'Review'])->nullable();
            $table->float('net_total');
            $table->float('net_discount');
            $table->float('net_vat');
            $table->text('ref_no')->nullable();
            $table->float('grand_total');
            $table->float('amount');
            $table->integer('created_by');
            $table->dateTime('approved_on')->nullable();
            $table->integer('approved_by')->nullable();
            $table->integer('draft')->default(0);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estimates');
    }
};
