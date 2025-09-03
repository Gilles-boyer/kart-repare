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
        Schema::create('request_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
            $table->string('hex_color', 7)->default('#6c757d'); // Format #RRGGBB
            $table->boolean('is_final')->default(false);
            $table->timestamps();
            $table->softDeletes();

            // Index pour les performances
            $table->index(['is_final']);
            $table->index(['name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('request_statuses');
    }
};
