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
        Schema::create('trip_ticket_rows', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("trip_ticket_id");
            $table->string("departure");
            $table->string("destination");
            $table->decimal("distance", 12, 2)->default(0);
            $table->decimal("quantity", 12, 2)->default(0);
            $table->date('date');

            $table->foreign("trip_ticket_id")->references("id")->on("trip_tickets")->onDelete("cascade");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trip_ticket_rows');
    }
};
