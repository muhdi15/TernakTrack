<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('animal_fence', function (Blueprint $table) {
            $table->id();
            $table->foreignId('animal_id')->constrained('animals')->cascadeOnDelete();
            $table->foreignId('fence_id')->constrained('fences')->cascadeOnDelete();
            $table->timestamp('assigned_at')->nullable()->comment('Waktu hewan ditugaskan ke fence');
            $table->unique(['animal_id', 'fence_id']);
        });

        Schema::table('animal_fence', function (Blueprint $table) {
            $table->comment('Pivot: penugasan hewan ke fence');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('animal_fence');
    }
};
