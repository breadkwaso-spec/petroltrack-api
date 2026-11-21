<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fuel_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grade_id')->constrained('fuel_grades')->cascadeOnDelete();
            $table->decimal('price_per_liter', 8, 2);
            $table->timestamp('effective_from')->useCurrent();
            $table->foreignId('set_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fuel_prices');
    }
};