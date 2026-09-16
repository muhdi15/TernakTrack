<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('animal_id')->nullable()->constrained('animals')->nullOnDelete();
            $table->foreignId('device_id')->nullable()->constrained('devices')->nullOnDelete();
            $table->foreignId('geofence_event_id')->nullable()->constrained('geofence_events')->nullOnDelete();
            $table->enum('type', ['fence_exit', 'fence_enter', 'low_battery', 'device_offline',
                'device_lost', 'health_reminder', 'calibration_complete', 'custom']);
            $table->enum('severity', ['info', 'warning', 'critical'])->default('warning');
            $table->string('title', 200);
            $table->text('message');
            $table->json('channels_sent')->nullable()->comment('List channel notifikasi yang berhasil terkirim');
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'is_read', 'created_at']);
            $table->index('severity');
        });

        Schema::table('alerts', function (Blueprint $table) {
            $table->comment('Notifikasi/peringatan untuk pengguna');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};
