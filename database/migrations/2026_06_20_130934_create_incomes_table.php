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
        Schema::create('incomes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->nullable()->constrained()->onDelete('set null');
            $table->string('source', 100);
            $table->decimal('amount', 10, 2);
            $table->text('description')->nullable();
            $table->date('date');
            $table->string('payment_method', 20);
            $table->boolean('is_recurring')->default(false);
            $table->string('recurring_frequency', 10)->nullable();
            $table->date('recurring_end_date')->nullable();
            $table->string('receipt_image', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Indexes
            $table->index('user_id');
            $table->index(['user_id', 'date']);
            $table->index(['user_id', 'is_active']);
            $table->index('date');
            $table->index('is_recurring');
            $table->index('source');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incomes');
    }
};
