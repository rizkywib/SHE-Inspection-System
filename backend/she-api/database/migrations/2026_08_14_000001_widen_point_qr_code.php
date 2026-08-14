<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('point', function (Blueprint $table) {
            $indexes = collect(Schema::getIndexes('point'));
            $unique = $indexes->first(fn ($index) => in_array('qr_code', $index['columns'] ?? []) && ($index['unique'] ?? false));

            if ($unique) {
                $table->dropUnique($unique['name']);
            }

            $table->string('qr_code', 500)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('point', function (Blueprint $table) {
            $table->string('qr_code', 100)->nullable()->change();
            $table->unique('qr_code');
        });
    }
};
