<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('downloads', function (Blueprint $table) {
            $table->id();
        

            $table->string('title');

            $table->string('slug')->unique();

            $table->text('description')->nullable();

            $table->string('file_path');

            $table->string('thumbnail')->nullable();

            $table->string('version')->nullable();

            $table->unsignedBigInteger('download_count')
                ->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('downloads');
    }
};
