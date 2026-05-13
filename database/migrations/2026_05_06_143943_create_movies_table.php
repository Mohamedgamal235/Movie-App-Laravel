<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movies', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tmdb_id')->nullable()->index();
            $table->string('title')->unique();
            $table->string('genre');
            $table->decimal('rating', 3, 1);
            $table->text('notes')->nullable();
            $table->enum('status', ['Watching', 'Watched', 'Plan to Watch']);
            $table->string('poster_path', 2048);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movies');
    }
};
