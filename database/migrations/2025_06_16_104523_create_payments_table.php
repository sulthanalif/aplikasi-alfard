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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_id')->constrained('sales');
            $table->string('customer_id')->nullable();
            $table->boolean('status')->default(false);
            $table->timestamps();

            $table->foreign('customer_id')
                ->references('customer_id')
                ->on('users')
                ->nullOnDelete();
        });

        Schema::create('payment_details', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->foreignId('payment_id')->constrained('payments');
            $table->decimal('amount', 12, 2);
            $table->enum('method', ['cash', 'transfer']);
            $table->string('bank')->nullable()->default('-');
            $table->string('image')->nullable()->default('-');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
