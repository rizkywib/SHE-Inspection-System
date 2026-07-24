<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('fire_hydrant_inspections', 'status')) {
            Schema::table('fire_hydrant_inspections', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }
    }

    public function down(): void
    {
        Schema::table('fire_hydrant_inspections', function (Blueprint $table) {
            $table->string('status', 50)->nullable()->after('notes');
        });
    }
};