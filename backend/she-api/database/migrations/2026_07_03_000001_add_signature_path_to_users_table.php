<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'signature_path')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('signature_path')->nullable()->after('last_login');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'signature_path')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('signature_path');
            });
        }
    }
};
