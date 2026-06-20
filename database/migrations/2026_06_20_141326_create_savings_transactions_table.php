<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('savings_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('savings_goal_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->string('type', 20);
            $table->string('source', 50)->nullable();
            $table->text('description')->nullable();
            $table->decimal('balance_after', 10, 2);
            $table->timestamps();

            $table->index('user_id');
            $table->index('savings_goal_id');
            $table->index('type');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('savings_transactions');
    }
};
