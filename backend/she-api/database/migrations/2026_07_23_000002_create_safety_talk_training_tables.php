<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('safety_talk_speakers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('safety_talk_trainings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('speaker_id')->constrained('safety_talk_speakers')->restrictOnDelete();
            $table->date('implementation_date');
            $table->text('topic');
            $table->unsignedInteger('ecogreen_participants')->default(0);
            $table->unsignedInteger('outsourcing_participants')->default(0);
            $table->unsignedInteger('contractor_participants')->default(0);
            $table->unsignedInteger('duration_minutes');
            $table->unsignedTinyInteger('implementation_area');
            $table->string('activity_photo_path');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('implementation_date');
            $table->index('speaker_id');
            $table->index('implementation_area');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('safety_talk_trainings');
        Schema::dropIfExists('safety_talk_speakers');
    }
};
