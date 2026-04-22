<?php

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
        Schema::create('app_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid')->unique();
            $table->string('event_type', 64);
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->string('role_snapshot', 16)->nullable();
            $table->foreignId('plant_record_id')->nullable()->constrained('plant_records');
            $table->string('search_query')->nullable();
            $table->string('search_type', 32)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at')->useCurrent();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['event_type', 'occurred_at']);
            $table->index(['user_id', 'occurred_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_events');
    }
};
