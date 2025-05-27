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
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('invoice')->unique();
            $table->text('date');
            $table->string('customer_id')->nullable();
            $table->decimal('total_price', 13, 2);
            $table->text('address')->nullable();
            $table->foreignId('action_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('customer_id')
                ->references('customer_id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
