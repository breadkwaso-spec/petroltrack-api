<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fuel_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grade_id')->constrained('fuel_grades')->cascadeOnDelete();
            $table->decimal('liters', 10, 2);
            $table->decimal('cost_per_liter', 8, 2);
            $table->string('supplier');
            $table->timestamp('delivered_at')->useCurrent();
            $table->foreignId('recorded_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fuel_deliveries');
    }
};