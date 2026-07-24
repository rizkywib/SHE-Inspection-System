<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['area_id', 'qr_code_id', 'assigned_to', 'location_id'] as $column) {
            $foreignKey = DB::table('information_schema.KEY_COLUMN_USAGE')
                ->where('TABLE_SCHEMA', DB::getDatabaseName())
                ->where('TABLE_NAME', 'fire_extinguisher_inspections')
                ->where('COLUMN_NAME', $column)
                ->whereNotNull('REFERENCED_TABLE_NAME')
                ->value('CONSTRAINT_NAME');

            if ($foreignKey) {
                DB::statement(sprintf(
                    'ALTER TABLE `fire_extinguisher_inspections` DROP FOREIGN KEY `%s`',
                    str_replace('`', '``', $foreignKey)
                ));
            }
        }

        Schema::table('fire_extinguisher_inspections', function (Blueprint $table) {
            $table->integer('location_id')->nullable(false)->change();
            $table->foreign('location_id')
                ->references('id_location')
                ->on('fire_extinguisher_location')
                ->onDelete('RESTRICT');
        });

        $columnsToDrop = array_values(array_filter(
            ['area_id', 'qr_code_id', 'assigned_to', 'notes', 'status'],
            fn (string $column) => Schema::hasColumn('fire_extinguisher_inspections', $column)
        ));

        if ($columnsToDrop !== []) {
            Schema::table('fire_extinguisher_inspections', function (Blueprint $table) use ($columnsToDrop) {
                $table->dropColumn($columnsToDrop);
            });
        }
    }

    public function down(): void
    {
        Schema::table('fire_extinguisher_inspections', function (Blueprint $table) {
            $table->dropForeign(['location_id']);
            
            $table->unsignedBigInteger('location_id')->nullable()->change();
            $table->foreign('location_id')
                ->references('id')
                ->on('locations')
                ->onDelete('SET NULL');

            $table->unsignedBigInteger('area_id')->nullable();
            $table->foreign('area_id')->references('id')->on('areas')->onDelete('SET NULL');
            
            $table->unsignedBigInteger('qr_code_id')->nullable();
            $table->foreign('qr_code_id')->references('id')->on('asset_qr_codes')->onDelete('SET NULL');
            
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->foreign('assigned_to')->references('id')->on('users')->onDelete('SET NULL');
            
            $table->text('notes')->nullable()->after('signed_at');
            $table->enum('status', ['draft', 'completed', 'signed'])->default('draft')->after('notes');
        });
    }
};
