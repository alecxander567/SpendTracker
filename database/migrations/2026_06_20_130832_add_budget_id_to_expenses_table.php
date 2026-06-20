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
        Schema::table('expenses', function (Blueprint $table) {
            // Add budget_id column after category_id
            $table->foreignId('budget_id')
                ->nullable()
                ->after('category_id')
                ->constrained('budgets')
                ->onDelete('set null');

            // Add index for faster queries
            $table->index('budget_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            // Drop foreign key constraint first
            $table->dropForeign(['budget_id']);
            // Then drop the column
            $table->dropColumn('budget_id');
        });
    }
};
