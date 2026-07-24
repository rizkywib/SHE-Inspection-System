<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('permit_inspectors')) {
            Schema::create('permit_inspectors', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('permit_types')) {
            Schema::create('permit_types', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('supervision_areas')) {
            Schema::create('supervision_areas', function (Blueprint $table) {
                $table->id();
                $table->string('code', 50)->unique();
                $table->string('name');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('permit_main_areas')) {
            Schema::create('permit_main_areas', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('permit_sub_areas')) {
            Schema::create('permit_sub_areas', function (Blueprint $table) {
                $table->id();
                $table->foreignId('main_area_id')
                    ->nullable()
                    ->constrained('permit_main_areas')
                    ->nullOnDelete();
                $table->string('name')->unique();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('safe_work_permit_inspections')) {
            Schema::create('safe_work_permit_inspections', function (Blueprint $table) {
                $table->id();
                $table->date('permit_date');
                $table->foreignId('inspector_id')->constrained('permit_inspectors')->restrictOnDelete();
                $table->string('permit_number')->unique();
                $table->foreignId('permit_type_id')->constrained('permit_types')->restrictOnDelete();
                $table->foreignId('supervision_area_id')->constrained('supervision_areas')->restrictOnDelete();
                $table->foreignId('main_area_id')->constrained('permit_main_areas')->restrictOnDelete();
                $table->foreignId('sub_area_id')->constrained('permit_sub_areas')->restrictOnDelete();
                $table->string('section_equipment');
                $table->text('job_performance');
                $table->string('authorized_craftman');
                $table->string('authorized_facility');
                $table->string('contractor_name');
                $table->text('work_description');
                $table->text('permit_findings')->nullable();
                $table->timestamps();

                $table->index(['permit_date', 'id']);
                $table->index('contractor_name');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('safe_work_permit_inspections');
        Schema::dropIfExists('permit_sub_areas');
        Schema::dropIfExists('permit_main_areas');
        Schema::dropIfExists('supervision_areas');
        Schema::dropIfExists('permit_types');
        Schema::dropIfExists('permit_inspectors');
    }
};
