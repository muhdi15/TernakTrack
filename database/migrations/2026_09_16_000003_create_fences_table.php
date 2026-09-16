<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('farm_id')->nullable()->constrained('farms')->nullOnDelete();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->string('color', 9)->default('#22c55e');
            $table->enum('fence_type', ['inclusion', 'exclusion'])->default('inclusion')
                ->comment('inclusion = zona aman di dalam polygon; exclusion = zona larangan di dalam polygon');
            $table->json('polygon_coordinates')->comment('Array [{lat, lng}]');
            $table->decimal('area_hectares', 10, 4)->nullable();
            $table->unsignedInteger('version')->default(1)->comment('Versi polygon untuk audit perubahan');
            $table->boolean('is_active')->default(true);
            $table->boolean('alert_on_exit')->default(true);
            $table->boolean('alert_on_enter')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'is_active']);
            $table->index('farm_id');
        });

        Schema::table('fences', function (Blueprint $table) {
            $table->comment('Virtual fence berbentuk polygon');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fences');
    }
};
