<?php

use App\Enums\PlantCondition;
use App\Enums\VerificationStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('plant_records', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid')->unique();
            $table->string('public_id', 26)->unique();
            $table->foreignId('created_by_user_id')->constrained('users');
            $table->string('provisional_common_name', 120);
            $table->string('verified_common_name', 120)->nullable();
            $table->string('verified_scientific_name', 180)->nullable();
            $table->text('description')->nullable();
            $table->string('primary_photo_path');
            $table->string('plant_condition', 32)->default(PlantCondition::UNKNOWN->value);
            $table->string('verification_status', 16)->default(VerificationStatus::PENDING->value);
            $table->foreignId('verified_by_user_id')->nullable()->constrained('users');
            $table->timestamp('verified_at')->nullable();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->timestamp('latest_observation_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['verification_status', 'created_at']);
            $table->index(['latitude', 'longitude']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plant_records');
    }
};
