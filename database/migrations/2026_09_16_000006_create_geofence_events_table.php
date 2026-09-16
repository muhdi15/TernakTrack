<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('geofence_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('animal_id')->constrained('animals')->cascadeOnDelete();
            $table->foreignId('fence_id')->constrained('fences')->cascadeOnDelete();
            $table->foreignId('device_id')->constrained('devices')->cascadeOnDelete();
            $table->enum('event_type', ['exit', 'enter', 'inside', 'outside']);
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('distance_from_fence_meters', 10, 2)->nullable();
            $table->boolean('is_acknowledged')->default(false);
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('acknowledged_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['animal_id', 'created_at']);
            $table->index(['fence_id', 'created_at']);
            $table->index('is_acknowledged');
        });

        Schema::table('geofence_events', function (Blueprint $table) {
            $table->comment('Event keluar/masuk zona fence hasil ray casting');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('geofence_events');
    }
};
