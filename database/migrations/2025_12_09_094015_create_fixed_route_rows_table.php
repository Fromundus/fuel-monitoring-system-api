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
        Schema::create('fixed_route_rows', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("fixed_route_id");
            $table->string("departure");
            $table->string("destination");
            $table->decimal("distance", 12, 2)->default(0);
            $table->decimal("quantity", 12, 2)->default(0);
            $table->date('date');

            $table->foreign("fixed_route_id")->references("id")->on("fixed_routes")->onDelete("cascade");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fixed_route_rows');
    }
};
