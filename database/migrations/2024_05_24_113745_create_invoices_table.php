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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->text('invoice_number')->nullable();
            $table->integer('job_id')->nullable();
            $table->enum('pay_status',[0,1,2,3])->comment('0=Un Paid, 1=Paid, 2=Partial, 3=Overdue')->nullable();
            $table->enum('type',[0,1])->comment('0=Bill, 1=Invoice')->nullable();
            $table->enum('payment_type',[0,1,2,3])->comment('0=Cash, 1=Cheque, 2=Credit Card, 3=Cash Advance')->nullable();
            $table->timestamp('due_date')->nullable();
            $table->timestamp('last_received')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
