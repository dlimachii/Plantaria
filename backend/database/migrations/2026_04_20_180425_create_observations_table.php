<?php

use App\Enums\ObservationSourceType;
use App\Enums\PlantCondition;
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
        Schema::create('observations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid')->unique();
            $table->string('public_id', 26)->unique();
            $table->foreignId('plant_record_id')->constrained('plant_records');
            $table->foreignId('author_user_id')->constrained('users');
            $table->string('photo_path');
            $table->text('note')->nullable();
            $table->string('plant_condition', 32)->default(PlantCondition::UNKNOWN->value);
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->string('source_type', 16)->default(ObservationSourceType::UPDATE->value);
            $table->timestamp('observed_at');
            $table->timestamps();

            $table->index(['plant_record_id', 'observed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('observations');
    }
};
