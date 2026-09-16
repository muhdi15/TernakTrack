<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calibration_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('fence_id')->nullable()->constrained('fences')->nullOnDelete()
                ->comment('Null jika masih draft / belum jadi fence');
            $table->string('session_token', 64)->unique()->comment('Token link kalibrasi di HP');
            $table->string('name', 150)->nullable()->comment('Nama sementara fence');
            $table->json('points')->comment('Array [{lat, lng, accuracy, recorded_at, order}]');
            $table->enum('status', ['draft', 'completed', 'cancelled', 'used'])->default('draft');
            $table->timestamp('expires_at');
            $table->timestamp('completed_at')->nullable();
            $table->json('device_info')->nullable()->comment('User agent / info HP');
            $table->timestamps();

            $table->index('session_token');
            $table->index(['user_id', 'status']);
            $table->index('expires_at');
            $table->index('fence_id');
        });

        Schema::table('calibration_sessions', function (Blueprint $table) {
            $table->comment('Sesi kalibrasi fence memakai GPS smartphone');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calibration_sessions');
    }
};
