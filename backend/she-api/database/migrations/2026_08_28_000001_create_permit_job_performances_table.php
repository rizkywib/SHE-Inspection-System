<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('permit_job_performances')) {
            Schema::create('permit_job_performances', function (Blueprint $table) {
                $table->id();
                $table->string('name', 255)->unique();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        $existing = DB::table('safe_work_permit_inspections')
            ->whereNotNull('job_performance')
            ->whereRaw('TRIM(job_performance) <> ""')
            ->distinct()
            ->pluck('job_performance');

        foreach ($existing as $value) {
            $name = mb_substr(trim((string) $value), 0, 255);
            if ($name === '') {
                continue;
            }
            DB::table('permit_job_performances')->updateOrInsert(
                ['name' => $name],
                ['is_active' => true, 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('permit_job_performances');
    }
};