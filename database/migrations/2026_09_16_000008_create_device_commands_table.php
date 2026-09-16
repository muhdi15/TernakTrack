<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_commands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained('devices')->cascadeOnDelete();
            $table->string('command', 64)->comment('set_interval, reboot, locate_now, dst.');
            $table->json('payload')->nullable();
            $table->enum('status', ['pending', 'sent', 'executed', 'failed'])->default('pending');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->timestamps();

            $table->index(['device_id', 'status']);
        });

        Schema::table('device_commands', function (Blueprint $table) {
            $table->comment('Perintah yang dikirim ke perangkat ESP32');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_commands');
    }
};
