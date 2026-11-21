<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fuel_stocks', function (Blueprint $table) {
            $table->foreignId('grade_id')->primary()->constrained('fuel_grades')->cascadeOnDelete();
            $table->decimal('liters_on_hand', 10, 2)->default(0);
            $table->decimal('low_stock_threshold', 10, 2)->default(500);
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fuel_stocks');
    }
};