<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('safe_work_permit_inspections')
            && Schema::hasColumn('safe_work_permit_inspections', 'inspector_id')
            && !Schema::hasColumn('safe_work_permit_inspections', 'legacy_inspector_id')) {
            Schema::table('safe_work_permit_inspections', function (Blueprint $table) {
                $table->dropForeign(['inspector_id']);
            });
            Schema::table('safe_work_permit_inspections', function (Blueprint $table) {
                $table->renameColumn('inspector_id', 'legacy_inspector_id');
            });
            Schema::table('safe_work_permit_inspections', function (Blueprint $table) {
                $table->unsignedBigInteger('legacy_inspector_id')->nullable()->change();
                $table->foreign('legacy_inspector_id')
                    ->references('id')
                    ->on('permit_inspectors')
                    ->nullOnDelete();
                $table->foreignId('inspector_id')
                    ->nullable()
                    ->after('permit_date')
                    ->constrained('users')
                    ->nullOnDelete();
            });

            $this->mapLegacyNamesToUsers(
                'safe_work_permit_inspections',
                'legacy_inspector_id',
                'permit_inspectors',
                'inspector_id'
            );
        }

        if (Schema::hasTable('safety_talk_trainings')
            && Schema::hasColumn('safety_talk_trainings', 'speaker_id')
            && !Schema::hasColumn('safety_talk_trainings', 'legacy_speaker_id')) {
            Schema::table('safety_talk_trainings', function (Blueprint $table) {
                $table->dropForeign(['speaker_id']);
            });
            Schema::table('safety_talk_trainings', function (Blueprint $table) {
                $table->renameColumn('speaker_id', 'legacy_speaker_id');
            });
            Schema::table('safety_talk_trainings', function (Blueprint $table) {
                $table->unsignedBigInteger('legacy_speaker_id')->nullable()->change();
                $table->foreign('legacy_speaker_id')
                    ->references('id')
                    ->on('safety_talk_speakers')
                    ->nullOnDelete();
                $table->foreignId('speaker_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('users')
                    ->nullOnDelete();
            });

            $this->mapLegacyNamesToUsers(
                'safety_talk_trainings',
                'legacy_speaker_id',
                'safety_talk_speakers',
                'speaker_id'
            );
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('safety_talk_trainings')
            && Schema::hasColumn('safety_talk_trainings', 'legacy_speaker_id')
            && Schema::hasColumn('safety_talk_trainings', 'speaker_id')) {
            $this->mapUsersBackToLegacy(
                'safety_talk_trainings',
                'speaker_id',
                'safety_talk_speakers',
                'legacy_speaker_id'
            );

            Schema::table('safety_talk_trainings', function (Blueprint $table) {
                $table->dropForeign(['speaker_id']);
                $table->dropColumn('speaker_id');
                $table->dropForeign(['legacy_speaker_id']);
            });
            Schema::table('safety_talk_trainings', function (Blueprint $table) {
                $table->unsignedBigInteger('legacy_speaker_id')->nullable(false)->change();
                $table->renameColumn('legacy_speaker_id', 'speaker_id');
            });
            Schema::table('safety_talk_trainings', function (Blueprint $table) {
                $table->foreign('speaker_id')
                    ->references('id')
                    ->on('safety_talk_speakers')
                    ->restrictOnDelete();
            });
        }

        if (Schema::hasTable('safe_work_permit_inspections')
            && Schema::hasColumn('safe_work_permit_inspections', 'legacy_inspector_id')
            && Schema::hasColumn('safe_work_permit_inspections', 'inspector_id')) {
            $this->mapUsersBackToLegacy(
                'safe_work_permit_inspections',
                'inspector_id',
                'permit_inspectors',
                'legacy_inspector_id'
            );

            Schema::table('safe_work_permit_inspections', function (Blueprint $table) {
                $table->dropForeign(['inspector_id']);
                $table->dropColumn('inspector_id');
                $table->dropForeign(['legacy_inspector_id']);
            });
            Schema::table('safe_work_permit_inspections', function (Blueprint $table) {
                $table->unsignedBigInteger('legacy_inspector_id')->nullable(false)->change();
                $table->renameColumn('legacy_inspector_id', 'inspector_id');
            });
            Schema::table('safe_work_permit_inspections', function (Blueprint $table) {
                $table->foreign('inspector_id')
                    ->references('id')
                    ->on('permit_inspectors')
                    ->restrictOnDelete();
            });
        }
    }

    private function mapLegacyNamesToUsers(
        string $recordsTable,
        string $legacyColumn,
        string $legacyTable,
        string $userColumn
    ): void {
        DB::table($recordsTable)
            ->whereNotNull($legacyColumn)
            ->orderBy('id')
            ->get(['id', $legacyColumn])
            ->each(function (object $record) use ($recordsTable, $legacyColumn, $legacyTable, $userColumn) {
                $legacyName = DB::table($legacyTable)->where('id', $record->{$legacyColumn})->value('name');
                $userId = $legacyName
                    ? DB::table('users')
                        ->whereRaw('LOWER(TRIM(name)) = LOWER(TRIM(?))', [$legacyName])
                        ->orderByDesc('is_active')
                        ->orderBy('id')
                        ->value('id')
                    : null;

                DB::table($recordsTable)->where('id', $record->id)->update([$userColumn => $userId]);
            });
    }

    private function mapUsersBackToLegacy(
        string $recordsTable,
        string $userColumn,
        string $legacyTable,
        string $legacyColumn
    ): void {
        DB::table($recordsTable)
            ->whereNull($legacyColumn)
            ->whereNotNull($userColumn)
            ->orderBy('id')
            ->get(['id', $userColumn])
            ->each(function (object $record) use ($recordsTable, $userColumn, $legacyTable, $legacyColumn) {
                $name = DB::table('users')->where('id', $record->{$userColumn})->value('name');

                if (!$name) {
                    return;
                }

                $legacyId = DB::table($legacyTable)
                    ->whereRaw('LOWER(TRIM(name)) = LOWER(TRIM(?))', [$name])
                    ->value('id');

                if (!$legacyId) {
                    $legacyId = DB::table($legacyTable)->insertGetId([
                        'name' => $name,
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                DB::table($recordsTable)->where('id', $record->id)->update([$legacyColumn => $legacyId]);
            });
    }
};
