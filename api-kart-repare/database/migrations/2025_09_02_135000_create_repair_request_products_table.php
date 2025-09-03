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
        Schema::create('repair_request_products', function (Blueprint $table) {
            $table->id();

            // Foreign keys
            $table->foreignId('repair_request_id')
                  ->constrained('repair_requests')
                  ->onDelete('cascade');

            $table->foreignId('product_id')
                  ->constrained('products')
                  ->onDelete('restrict');

            // Product details for this repair request
            $table->integer('quantity')->default(1);
            $table->enum('priority', ['high', 'medium', 'low'])->default('medium');
            $table->text('note')->nullable();

            // Pricing information
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_price', 10, 2);

            // Workflow tracking
            $table->foreignId('invoiced_by')
                  ->nullable()
                  ->constrained('users')
                  ->onDelete('set null');
            $table->timestamp('invoiced_at')->nullable();

            $table->foreignId('completed_by')
                  ->nullable()
                  ->constrained('users')
                  ->onDelete('set null');
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('approved_at')->nullable();

            // Standard timestamps
            $table->timestamps();

            // Indexes for performance
            $table->index('repair_request_id');
            $table->index('product_id');
            $table->index('priority');
            $table->index('invoiced_by');
            $table->index('completed_by');
            $table->index('invoiced_at');
            $table->index('completed_at');
            $table->index('approved_at');

            // Composite indexes
            $table->index(['repair_request_id', 'product_id'], 'rr_product_idx');
            $table->index(['repair_request_id', 'priority'], 'rr_priority_idx');
            $table->index(['product_id', 'completed_at'], 'product_completion_idx');

            // Unique constraint to avoid duplicate products in same repair request
            $table->unique(['repair_request_id', 'product_id'], 'unique_repair_product');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('repair_request_products');
    }
};
