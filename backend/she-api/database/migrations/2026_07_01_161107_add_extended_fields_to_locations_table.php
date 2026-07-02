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
        Schema::table('locations', function (Blueprint $table) {
            $table->text('keterangan1')->nullable()->after('address');
            $table->text('keterangan2')->nullable()->after('keterangan1');
            $table->string('status')->default('active')->after('keterangan2');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn(['keterangan1', 'keterangan2', 'status']);
        });
    }
};
