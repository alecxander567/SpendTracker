<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Use raw SQL with IF EXISTS for PostgreSQL
        DB::statement('ALTER TABLE savings_goals DROP COLUMN IF EXISTS current_amount');
    }

    public function down(): void
    {
        Schema::table('savings_goals', function (Blueprint $table) {
            $table->decimal('current_amount', 10, 2)->default(0)->after('target_amount');
        });
    }
};
