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
            $table->string("plate_number")->nullable();
            $table->string("purpose");

            $table->decimal("quantity", 12, 2)->default(0);
            $table->string("unit");
            $table->integer("fuel_type_id");
            $table->string("fuel_type");

            $table->string("checked_by")->nullable();
            $table->date("checked_by_date")->nullable();
            $table->string("recommending_approval")->nullable();
            $table->date("recommending_approval_date")->nullable();
            $table->string("approved_by")->nullable();
            $table->date("approved_by_date")->nullable();
            $table->string("posted_by")->nullable();
            $table->date("posted_by_date")->nullable();

            $table->string("type"); // allowance, trip-ticket, emergency, delegated

            $table->enum("status", ['pending', 'approved', 'rejected', 'released', 'cancelled'])->default('pending'); // pending, approved, rejected, released, cancelled

            $table->string("source")->default('ficelco');

            $table->date('date');

            $table->string("reference_number")->unique();
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
