<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_battery_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained('devices')->cascadeOnDelete();
            $table->unsignedInteger('battery_level')->comment('Persentase baterai 0-100');
            $table->decimal('battery_voltage', 6, 3)->nullable()->comment('Tegangan baterai (V)');
            $table->timestamp('recorded_at')->comment('Waktu pengukuran/sumber laporan');
            $table->timestamps();

            $table->index(['device_id', 'recorded_at']);
        });

        Schema::table('device_battery_logs', function (Blueprint $table) {
            $table->comment('Riwayat level baterai perangkat (untuk grafik 7 hari)');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_battery_logs');
    }
};
