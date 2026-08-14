<?php

namespace App\Console\Commands;

use App\Models\Point;
use Illuminate\Console\Command;

class RegeneratePointQrCodes extends Command
{
    protected $signature = 'points:regenerate-qr';

    protected $description = 'Regenerate qr_code for all points using name_point, ket1, ket2';

    public function handle(): int
    {
        $count = 0;

        Point::orderBy('id')->chunkById(200, function ($points) use (&$count) {
            foreach ($points as $point) {
                $parts = array_values(array_filter([
                    trim((string) $point->name_point),
                    trim((string) $point->ket1),
                    trim((string) $point->ket2),
                ], fn ($value) => $value !== ''));

                $point->forceFill([
                    'qr_code' => implode("\n", $parts),
                    'qr_generated_at' => now(),
                ])->save();

                $count++;
            }
        });

        $this->info("Regenerated qr_code for {$count} points.");

        return self::SUCCESS;
    }
}
