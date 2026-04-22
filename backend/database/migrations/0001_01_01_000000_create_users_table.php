<?php

use App\Enums\UserRole;
use App\Enums\UserStatus;
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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid')->unique();
            $table->string('handle', 16)->unique();
            $table->string('display_name', 120);
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('photo_path')->nullable();
            $table->string('country', 100);
            $table->string('province', 100)->nullable();
            $table->string('city', 100)->nullable();
            $table->decimal('default_lat', 10, 7)->nullable();
            $table->decimal('default_lng', 10, 7)->nullable();
            $table->date('birthdate')->nullable();
            $table->string('role', 16)->default(UserRole::USER->value);
            $table->string('status', 16)->default(UserStatus::ACTIVE->value);
            $table->timestamp('last_login_at')->nullable();
            $table->decimal('last_known_lat', 10, 7)->nullable();
            $table->decimal('last_known_lng', 10, 7)->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['role', 'status']);
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
