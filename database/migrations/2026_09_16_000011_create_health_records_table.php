<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('health_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('animal_id')->constrained('animals')->cascadeOnDelete();
            $table->date('record_date');
            $table->enum('type', ['vaksinasi', 'pemeriksaan', 'pengobatan', 'penimbangan', 'lainnya']);
            $table->text('description');
            $table->string('vet_name', 150)->nullable();
            $table->date('next_due_date')->nullable()->comment('Jadwal tindakan berikutnya');
            $table->timestamps();

            $table->index(['animal_id', 'record_date']);
            $table->index('next_due_date');
        });

        Schema::table('health_records', function (Blueprint $table) {
            $table->comment('Riwayat kesehatan hewan');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('health_records');
    }
};
