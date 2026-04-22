<?php

use App\Enums\FlagStatus;
use App\Enums\FlagTargetType;
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
        Schema::create('moderation_flags', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid')->unique();
            $table->string('target_type', 24)->default(FlagTargetType::RECORD->value);
            $table->unsignedBigInteger('target_id');
            $table->foreignId('created_by_user_id')->constrained('users');
            $table->text('reason');
            $table->string('status', 16)->default(FlagStatus::OPEN->value);
            $table->foreignId('resolved_by_user_id')->nullable()->constrained('users');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['target_type', 'target_id']);
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('moderation_flags');
    }
};
