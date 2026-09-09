<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('fire_alarm_location')) {
            Schema::create('fire_alarm_location', function (Blueprint $table) {
                $table->integer('id_location', true);
                $table->string('name', 200);
                $table->timestamp('created')->useCurrent();
            });
        }

        if (Schema::hasTable('fire_alarm_inspections')) {
            $foreignKey = DB::table('information_schema.KEY_COLUMN_USAGE')
                ->where('TABLE_SCHEMA', DB::getDatabaseName())
                ->where('TABLE_NAME', 'fire_alarm_inspections')
                ->where('COLUMN_NAME', 'location_id')
                ->whereNotNull('REFERENCED_TABLE_NAME')
                ->value('CONSTRAINT_NAME');

            if ($foreignKey) {
                DB::statement(sprintf(
                    'ALTER TABLE `fire_alarm_inspections` DROP FOREIGN KEY `%s`',
                    str_replace('`', '``', $foreignKey)
                ));
            }

            Schema::table('fire_alarm_inspections', function (Blueprint $table) {
                $table->integer('location_id')->nullable()->change();
                $table->foreign('location_id')
                    ->references('id_location')
                    ->on('fire_alarm_location')
                    ->onDelete('RESTRICT');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('fire_alarm_inspections')) {
            $foreignKey = DB::table('information_schema.KEY_COLUMN_USAGE')
                ->where('TABLE_SCHEMA', DB::getDatabaseName())
                ->where('TABLE_NAME', 'fire_alarm_inspections')
                ->where('COLUMN_NAME', 'location_id')
                ->whereNotNull('REFERENCED_TABLE_NAME')
                ->value('CONSTRAINT_NAME');

            if ($foreignKey) {
                DB::statement(sprintf(
                    'ALTER TABLE `fire_alarm_inspections` DROP FOREIGN KEY `%s`',
                    str_replace('`', '``', $foreignKey)
                ));
            }

            Schema::table('fire_alarm_inspections', function (Blueprint $table) {
                $table->foreign('location_id')
                    ->references('id')
                    ->on('locations')
                    ->onDelete('SET NULL');
            });
        }

        Schema::dropIfExists('fire_alarm_location');
    }
};
