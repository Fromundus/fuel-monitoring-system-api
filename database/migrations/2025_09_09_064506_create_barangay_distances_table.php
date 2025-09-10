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
        Schema::create('barangay_distances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('barangay_a_id');
            $table->unsignedBigInteger('barangay_b_id');
            $table->unsignedBigInteger('distance_meters')->nullable(); // GraphHopper returns meters (float). store unsigned int.
            $table->unsignedBigInteger('time_ms')->nullable(); // route time in ms
            $table->json('route_raw')->nullable(); // store raw JSON response if you want
            
            $table->foreign('barangay_a_id')->references('id')->on('barangays')->onDelete('cascade');
            $table->foreign('barangay_b_id')->references('id')->on('barangays')->onDelete('cascade');
            
            // Ensure unique unordered pair: we will always store with a_id < b_id
            $table->unique(['barangay_a_id', 'barangay_b_id']);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('barangay_distances');
    }
};
