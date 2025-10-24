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
        Schema::create('requests', function (Blueprint $table) {
            $table->id();
            $table->integer("employeeid");
            $table->string("requested_by");

            $table->integer("delegatedtoid")->nullable();
            $table->string("delegated_to")->nullable();

            $table->string("department");
            $table->string("division")->nullable();

            $table->unsignedBigInteger('vehicle_id');

            $table->decimal('fuel_divisor', 12, 2)->default(0);
            
            $table->string("purpose");

            $table->decimal("quantity", 12, 2)->default(0);
            $table->string("unit");
            $table->integer("fuel_type_id");
            $table->string("fuel_type");

            $table->string("approved_by")->nullable();
            $table->timestamp("approved_date")->nullable();
            
            $table->string("released_by")->nullable();
            $table->string("released_to")->nullable();
            $table->timestamp("released_date")->nullable();

            $table->string("type"); // allowance, trip-ticket, emergency, delegated

            $table->enum("status", ['pending', 'approved', 'rejected', 'released', 'cancelled'])->default('pending'); // pending, approved, rejected, released, cancelled

            $table->string("source")->default('ficelco');

            $table->timestamp('date');

            $table->string("reference_number")->unique();

            $table->text('remarks')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('requests');
    }
};
