<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('es_ew_inspections', 'signed_by')) {
            Schema::table('es_ew_inspections', function (Blueprint $table) {
                $table->foreignId('signed_by')
                    ->nullable()
                    ->after('signed_at')
                    ->constrained('users')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('es_ew_inspections', 'signed_by')) {
            Schema::table('es_ew_inspections', function (Blueprint $table) {
                $table->dropConstrainedForeignId('signed_by');
            });
        }
    }
};
