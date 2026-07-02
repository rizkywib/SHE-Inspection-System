<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('point', 'qr_code')) {
            Schema::table('point', function (Blueprint $table) {
                $table->string('qr_code', 100)->nullable()->unique()->after('ket2');
            });
        }

        if (!Schema::hasColumn('point', 'qr_generated_at')) {
            Schema::table('point', function (Blueprint $table) {
                $table->timestamp('qr_generated_at')->nullable()->after('qr_code');
            });
        }
    }

    public function down(): void
    {
        Schema::table('point', function (Blueprint $table) {
            if (Schema::hasColumn('point', 'qr_generated_at')) {
                $table->dropColumn('qr_generated_at');
            }

            if (Schema::hasColumn('point', 'qr_code')) {
                $table->dropUnique(['qr_code']);
                $table->dropColumn('qr_code');
            }
        });
    }
};
