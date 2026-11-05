<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movies', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->unsignedSmallInteger('year');
            $table->string('director');
            $table->decimal('rating', 3, 1)->default(0);
            $table->string('duration', 20);
            $table->text('synopsis');
            $table->string('poster_url', 500);
            $table->json('cast_members');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movies');
    }
};