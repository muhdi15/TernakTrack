<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('animals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('farm_id')->nullable()->constrained('farms')->nullOnDelete();
            $table->foreignId('device_id')->nullable()->constrained('devices')->nullOnDelete();
            $table->string('name', 150);
            $table->string('tag_number', 64)->unique()->comment('Nomor tag identifikasi hewan');
            $table->enum('species', ['sapi', 'kambing', 'domba', 'kerbau', 'lainnya']);
            $table->enum('gender', ['jantan', 'betina']);
            $table->date('birth_date')->nullable();
            $table->decimal('weight_kg', 8, 2)->nullable();
            $table->string('photo_path')->nullable();
            $table->enum('health_status', ['sehat', 'sakit', 'hamil', 'karantina'])->default('sehat');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'health_status']);
            $table->index('farm_id');
        });

        Schema::table('animals', function (Blueprint $table) {
            $table->comment('Data hewan ternak');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('animals');
    }
};
