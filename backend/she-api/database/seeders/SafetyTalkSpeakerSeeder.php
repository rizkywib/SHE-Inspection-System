<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SafetyTalkSpeakerSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $speakers = [
            'ANDI ALIF BINTANG',
            'BENHARD PARNINGOTAN',
            'DWI NUR RADIVAN',
            'GERRY PRASETYA',
            'HARADI',
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
        ];

        foreach ($speakers as $name) {
            DB::table('safety_talk_speakers')->updateOrInsert(
                ['name' => $name],
                ['is_active' => true, 'updated_at' => $now, 'created_at' => $now]
            );
        }
    }
}
