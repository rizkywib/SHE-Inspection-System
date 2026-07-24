<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermitMatrixMasterSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $this->seedNames('permit_inspectors', [
            'ANDI ALIF BINTANG',
            'BENHARD PARNINGOTAN',
            'DWI NUR RADIVAN',
            'GERRY PRASETYA',
            'HARIADI',
            'JUNAIDI',
            'M AZHAR DIPRATAMA HSB',
            'M ILHAM ANSARI',
            'NOVA JOHANIS',
            'RAHMADY IBNU AGUNG',
            'RONI PASKAH SINAGA',
            'RUDI PRATAMA P',
            'SUSANTO',
            'WENDY G H',
            'WENDY GABRIEL H',
            'WENDY GABRIEL HUTAGALUNG',
            'WISONIR',
            'AL MUDZAWWIR',
            'YOKHA WINARTA',
        ], $now);

        $this->seedNames('permit_types', [
            'HOT WORK',
            'COOL WORK',
            'CONFINED SPACE ENTRY',
            'EXCAVATION',
        ], $now);

        foreach (range(1, 6) as $area) {
            DB::table('supervision_areas')->updateOrInsert(
                ['code' => (string) $area],
                ['name' => (string) $area, 'is_active' => true, 'updated_at' => $now, 'created_at' => $now]
            );
        }

        $this->seedNames('permit_main_areas', [
            'EOB1',
            'EOB2',
            'EOB3',
            'EOMB',
            'UTILITY POWER PLANT',
            'JETTY 1 & JETTY 2',
            'NON PROSES',
        ], $now);

        $this->seedNames('permit_sub_areas', [
            'METHYLESTER',
            'MPR PLANT',
            'FATTY ALCOHOL',
            'UTILITY',
            'PP&H',
            'TANK FARM',
            'FATTY ACID',
            'POWERPLANT',
            'JETTY 1',
            'JETTY 2',
            'BMS',
            'QA / LAB',
            'R & D',
        ], $now);
    }

    private function seedNames(string $table, array $names, mixed $now): void
    {
        foreach ($names as $name) {
            DB::table($table)->updateOrInsert(
                ['name' => $name],
                ['is_active' => true, 'updated_at' => $now, 'created_at' => $now]
            );
        }
    }
}
