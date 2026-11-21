<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fuel_sales', function (Blueprint $table) {
            $table->id();
            $table->uuid('entry_id')->unique();
            $table->foreignId('shift_id')->constrained('shifts')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('pump_id')->constrained('pumps')->cascadeOnDelete();
            $table->foreignId('grade_id')->constrained('fuel_grades')->cascadeOnDelete();
            $table->decimal('liters', 8, 2);
            $table->decimal('unit_price', 8, 2);
            $table->decimal('total_amount', 10, 2);
            $table->enum('payment_method', ['Cash', 'Card', 'Loan']);
            $table->enum('status', ['Valid', 'Void Requested', 'Voided', 'Flagged'])->default('Valid');
            $table->timestamp('recorded_at');
            $table->timestamp('synced_at')->nullable();
            $table->enum('source', ['Online', 'Offline'])->default('Online');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fuel_sales');
    }
};