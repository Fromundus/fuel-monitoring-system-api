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
        Schema::create('trip_tickets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("request_id");
            $table->string("plate_number")->nullable();
            $table->string("driver");
            $table->date('date');

            $table->decimal('milestone_value', 8, 2)->nullable();
            $table->decimal('liters_per_milestone', 8, 2)->nullable();
            $table->timestamp('settings_snapshot_at')->nullable();

            $table->foreign("request_id")->references("id")->on("requests")->onDelete("cascade");
            
            $table->timestamps();

            $table->index(['milestone_value', 'liters_per_milestone']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trip_tickets');
    }
};
