<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grades', function (Blueprint $table) {
            $table->id();

            $table->foreignId('subject_id')
                  ->nullable()
                  ->constrained('subjects')  // properly enforced FK
                  ->nullOnDelete();

            $table->string('informacijas_tips')->nullable();
            $table->string('informacijas_veids')->nullable();
            $table->string('prieksmetu_veids')->nullable();
            $table->date('datums')->nullable();
            $table->string('vertejuma_tips')->nullable();
            $table->string('ilens')->nullable();
            $table->string('karklins')->nullable();
            $table->string('varizeja')->nullable();
            // no timestamps — kept intentionally absent
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grades');
    }
};