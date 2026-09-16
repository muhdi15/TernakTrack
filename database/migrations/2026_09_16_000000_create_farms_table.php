<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('farms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete()->comment('Pemilik farm');
            $table->string('name', 150);
            $table->text('address')->nullable();
            $table->decimal('latitude', 10, 7)->nullable()->comment('Koordinat pusat farm');
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('timezone', 64)->default('Asia/Jakarta');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'is_active']);
        });

        Schema::table('farms', function (Blueprint $table) {
            $table->comment('Peternakan / lahan ternak milik user');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('farms');
    }
};
