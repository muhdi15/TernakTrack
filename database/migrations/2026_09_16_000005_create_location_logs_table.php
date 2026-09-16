<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('location_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained('devices')->cascadeOnDelete();
            $table->foreignId('animal_id')->nullable()->constrained('animals')->nullOnDelete();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('altitude', 8, 2)->nullable()->comment('Ketinggian (m)');
            $table->decimal('speed_kmh', 7, 2)->nullable();
            $table->decimal('heading', 6, 2)->nullable()->comment('Arah (derajat)');
            $table->decimal('accuracy_meters', 7, 2)->nullable();
            $table->unsignedInteger('satellites')->nullable();
            $table->decimal('hdop', 5, 2)->nullable()->comment('Horizontal dilution of precision');
            $table->timestamp('recorded_at')->comment('Waktu diukur oleh GPS device');
            $table->timestamp('received_at')->comment('Waktu diterima server');
            $table->boolean('is_valid')->default(true)->comment('False jika fix GPS buruk / di luar toleransi');
            $table->timestamps();

            $table->index(['device_id', 'recorded_at']);
            $table->index(['animal_id', 'recorded_at']);
            $table->index('received_at');
            $table->index('is_valid');
        });

        Schema::table('location_logs', function (Blueprint $table) {
            $table->comment('Log posisi GPS dari perangkat');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('location_logs');
    }
};
