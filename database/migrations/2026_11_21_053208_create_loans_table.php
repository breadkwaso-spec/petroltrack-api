<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('borrower_id')->constrained('borrowers')->cascadeOnDelete();
            $table->foreignId('fuel_sale_id')->constrained('fuel_sales')->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->decimal('balance', 10, 2);
            $table->enum('status', ['UNPAID', 'PARTIAL', 'PAID'])->default('UNPAID');
            $table->timestamp('issued_at')->useCurrent();
            $table->date('due_date')->nullable();
            $table->timestamp('settled_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};