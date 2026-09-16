<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete()->comment('Pemilik perangkat');
            $table->foreignId('farm_id')->nullable()->constrained('farms')->nullOnDelete();
            $table->string('name', 150);
            $table->string('device_code', 64)->unique()->comment('Kode unik ESP32');
            $table->string('api_token', 64)->unique()->comment('Token autentikasi API untuk ESP32');
            $table->enum('status', ['active', 'inactive', 'maintenance', 'lost'])->default('active');
            $table->unsignedInteger('battery_level')->nullable()->comment('Persentase baterai 0-100');
            $table->decimal('battery_voltage', 6, 3)->nullable()->comment('Tegangan baterai (V)');
            $table->timestamp('last_seen_at')->nullable();
            $table->string('firmware_version', 32)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
            $table->index('last_seen_at');
            $table->index('farm_id');
        });

        Schema::table('devices', function (Blueprint $table) {
            $table->comment('Perangkat GPS tracker ESP32');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
