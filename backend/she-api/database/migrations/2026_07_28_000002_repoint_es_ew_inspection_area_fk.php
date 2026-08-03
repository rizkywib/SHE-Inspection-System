<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('es_ew_inspections', function (Blueprint $table) {
            $table->dropForeign('fk_esei_area');
        });

        $this->remapAreaIds('areas', 'es_ew_areas');

        Schema::table('es_ew_inspections', function (Blueprint $table) {
            $table->foreign('area_id', 'fk_esei_area')
                ->references('id')
                ->on('es_ew_areas')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('es_ew_inspections', function (Blueprint $table) {
            $table->dropForeign('fk_esei_area');
        });

        $this->remapAreaIds('es_ew_areas', 'areas');

        Schema::table('es_ew_inspections', function (Blueprint $table) {
            $table->foreign('area_id', 'fk_esei_area')
                ->references('id')
                ->on('areas')
                ->nullOnDelete();
        });
    }

    private function remapAreaIds(string $sourceTable, string $targetTable): void
    {
        DB::table('es_ew_inspections')
            ->whereNotNull('area_id')
            ->orderBy('id')
            ->each(function ($inspection) use ($sourceTable, $targetTable) {
                $sourceName = DB::table($sourceTable)
                    ->where('id', $inspection->area_id)
                    ->value('name');
                $targetId = $sourceName === null
                    ? null
                    : DB::table($targetTable)
                        ->where('name', $sourceName)
                        ->value('id');

                DB::table('es_ew_inspections')
                    ->where('id', $inspection->id)
                    ->update(['area_id' => $targetId]);
            });
    }
};
