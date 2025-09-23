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
        Schema::create('fuel_allowances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("employeeid");
            $table->date('week_start'); // monday
            $table->decimal('allowance', 8, 2); // 8 liters
            $table->decimal('carried_over', 8, 2)->default(0);
            $table->decimal('used', 8, 2)->default(0);
            $table->decimal('advanced', 8, 2)->default(0);
            $table->string('type'); // gasoline-diesel, 4t2t, bfluid
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fuel_allowances');
    }
};
