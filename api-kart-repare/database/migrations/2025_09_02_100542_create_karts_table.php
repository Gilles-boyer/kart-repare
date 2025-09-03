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
        Schema::create('karts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pilot_id')->constrained('pilots')->onDelete('cascade');
            $table->string('brand');
            $table->string('model');
            $table->string('chassis_number')->unique();
            $table->integer('year');
            $table->string('engine_type');
            $table->boolean('is_active')->default(true);
            $table->text('note')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Index pour optimiser les requêtes
            $table->index(['pilot_id']);
            $table->index(['brand']);
            $table->index(['year']);
            $table->index(['is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('karts');
    }
};
