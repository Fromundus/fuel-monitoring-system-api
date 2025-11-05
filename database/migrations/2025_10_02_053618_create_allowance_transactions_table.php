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
        Schema::create('allowance_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employeeid');

            $table->enum('type', ['trip-ticket', 'gasoline-diesel', '2t4t', 'b-fluid']);
            $table->enum('tx_type', ['grant', 'use', 'adjustment', 'reversal']);
            $table->decimal('quantity', 8, 2);
            $table->string('reference')->nullable();
            $table->timestamp('granted_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('allowance_transactions');
    }
};
