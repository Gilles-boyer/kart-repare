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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('ref', 100)->unique();
            $table->decimal('price', 10, 2);
            $table->string('image')->nullable();
            $table->integer('in_stock')->default(0);
            $table->enum('unity', ['piece', 'hours', 'liters', 'kg'])->default('piece');
            $table->integer('min_stock')->default(0);
            $table->timestamps();
            $table->softDeletes();

            // Indexes pour les performances
            $table->index(['name', 'ref']);
            $table->index(['in_stock', 'min_stock']);
            $table->index('unity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
