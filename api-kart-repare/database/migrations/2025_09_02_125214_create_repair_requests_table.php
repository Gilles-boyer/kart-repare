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
        Schema::create('repair_requests', function (Blueprint $table) {
            $table->id();

            // Kart being repaired
            $table->foreignId('kart_id')
                  ->constrained('karts')
                  ->onDelete('cascade');

            // Request details
            $table->string('title');
            $table->text('description')->nullable();

            // Status
            $table->foreignId('status_id')
                  ->constrained('request_statuses')
                  ->onDelete('restrict');

            // Priority
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium');

            // Users involved
            $table->foreignId('created_by')
                  ->constrained('users')
                  ->onDelete('restrict');

            $table->foreignId('assigned_to')
                  ->nullable()
                  ->constrained('users')
                  ->onDelete('set null');

            // Costs
            $table->decimal('estimated_cost', 10, 2)->default(0);
            $table->decimal('actual_cost', 10, 2)->default(0);

            // Timeline
            $table->date('estimated_completion')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            // Standard timestamps
            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index('kart_id');
            $table->index('status_id');
            $table->index('priority');
            $table->index('created_by');
            $table->index('assigned_to');
            $table->index('estimated_completion');
            $table->index(['priority', 'status_id']);
            $table->index(['kart_id', 'status_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('repair_requests');
    }
};
