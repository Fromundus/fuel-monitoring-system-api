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
            $table->string("department");
            $table->string("plate_number");
            $table->string("purpose");

            $table->decimal("quantity", 12, 2)->default(0);
            $table->string("unit");
            $table->integer("fuel_type_id");
            $table->string("fuel_type");

            $table->string("checked_by");
            $table->date("checked_by_date");
            $table->string("recommending_approval");
            $table->date("recommending_approval_date");
            $table->string("approved_by");
            $table->date("approved_by_date");
            $table->string("posted_by");
            $table->date("posted_by_date");

            $table->string("type"); // allowance, trip-ticket, emergency

            $table->date('date');
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
