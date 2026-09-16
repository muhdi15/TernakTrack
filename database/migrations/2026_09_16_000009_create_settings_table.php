<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('key', 100);
            $table->text('value');
            $table->timestamps();

            $table->unique(['user_id', 'key']);
        });

        Schema::table('settings', function (Blueprint $table) {
            $table->comment('Pengaturan key-value per user');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
