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
        Schema::create('distributions', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->date('date');
            $table->foreignId('user_id')->constrained();
            $table->boolean('status')->default(false);
            $table->timestamps();
        });

        Schema::create('distribution_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('distribution_id')->constrained();
            $table->foreignId('sales_id')->constrained();
            $table->enum('status', ['pending', 'shipped', 'delivered'])->default('pending');
            $table->timestamp('shipment_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->string('note')->nullable();
            $table->string('image')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('distributions');
    }
};
