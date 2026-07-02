<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->dropForeignKeyIfExists('fire_hydrant_inspections', 'fk_fhi_location');
        $this->dropForeignKeyIfExists('fire_hydrant_inspections', 'fk_fhi_hydrant_location');

        DB::statement('ALTER TABLE fire_hydrant_inspections MODIFY location_id INT(11) NULL');
        DB::statement('ALTER TABLE fire_hydrant_inspections ADD CONSTRAINT fk_fhi_hydrant_location FOREIGN KEY (location_id) REFERENCES fire_hydrant_location(id_location) ON DELETE SET NULL');
    }

    public function down(): void
    {
        $this->dropForeignKeyIfExists('fire_hydrant_inspections', 'fk_fhi_hydrant_location');

        DB::statement('ALTER TABLE fire_hydrant_inspections MODIFY location_id BIGINT(20) UNSIGNED NULL');
        DB::statement('ALTER TABLE fire_hydrant_inspections ADD CONSTRAINT fk_fhi_location FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE SET NULL');
    }

    private function dropForeignKeyIfExists(string $table, string $constraint): void
    {
        $database = DB::getDatabaseName();
        $exists = DB::selectOne(
            'SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = ?',
            [$database, $table, $constraint, 'FOREIGN KEY']
        );

        if ($exists) {
            DB::statement("ALTER TABLE {$table} DROP FOREIGN KEY {$constraint}");
        }
    }
};
