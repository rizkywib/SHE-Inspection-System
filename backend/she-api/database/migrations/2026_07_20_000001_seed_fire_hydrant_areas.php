<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('areas')->count() > 0) {
            return;
        }

        $locationId = DB::table('locations')->orderBy('id')->value('id');
        if (!$locationId) {
            return;
        }

        $now = now();
        $areas = DB::table('fire_hydrant_location')
            ->orderBy('id_location')
            ->pluck('name')
            ->filter()
            ->unique()
            ->map(fn ($name) => [
                'location_id' => $locationId,
                'name' => $name,
                'group_code' => 'FIRE_HYDRANT',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->values()
            ->all();

        if (!empty($areas)) {
            DB::table('areas')->insert($areas);
        }
    }

    public function down(): void
    {
        DB::table('areas')->where('group_code', 'FIRE_HYDRANT')->delete();
    }
};
