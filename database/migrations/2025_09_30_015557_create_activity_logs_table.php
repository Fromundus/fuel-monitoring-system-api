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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId("request_id")->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId("user_id")->nullable()->constrained("users")->nullOnDelete(); // who performed the action
            $table->unsignedBigInteger("employee_id");
            
            $table->string("action"); // e.g. created, approved, released, fuel_in, fuel_out
            $table->text("description")->nullable(); // free text explanation

            $table->unsignedBigInteger("item_id")->nullable();
            $table->string("item_name")->nullable();
            $table->string("item_unit")->nullable();
            $table->decimal("quantity", 12, 2)->nullable();

            $table->string("reference_number")->nullable();
            $table->string("reference_type")->nullable(); // e.g. FR
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
