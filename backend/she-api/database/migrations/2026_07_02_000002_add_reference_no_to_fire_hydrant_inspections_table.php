<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('fire_hydrant_inspections', 'reference_no')) {
            Schema::table('fire_hydrant_inspections', function (Blueprint $table) {
                $table->string('reference_no', 40)->nullable()->unique()->after('id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('fire_hydrant_inspections', 'reference_no')) {
            Schema::table('fire_hydrant_inspections', function (Blueprint $table) {
                $table->dropUnique(['reference_no']);
                $table->dropColumn('reference_no');
            });
        }
    }
};
