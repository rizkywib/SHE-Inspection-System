<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('fire_extinguisher_inspections', 'signed_by')) {
            Schema::table('fire_extinguisher_inspections', function (Blueprint $table) {
                $table->unsignedBigInteger('signed_by')->nullable()->after('signed_at');
                $table->foreign('signed_by')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('fire_extinguisher_inspections', 'signed_by')) {
            Schema::table('fire_extinguisher_inspections', function (Blueprint $table) {
                $table->dropForeign(['signed_by']);
                $table->dropColumn('signed_by');
            });
        }
    }
};
