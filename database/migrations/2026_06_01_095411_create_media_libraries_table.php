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
        Schema::create('media_libraries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('folder_id')
                ->nullable()
                ->constrained('media_folders')
                ->nullOnDelete();

            $table->string('collection_name')
                ->nullable();

            $table->string('file_name');

            $table->string('disk')
                ->default('public');

            $table->string('mime_type')
                ->nullable();

            $table->unsignedBigInteger('size')
                ->default(0);

            $table->string('path');

            $table->json('custom_properties')
                ->nullable();

            $table->string('alt_text')
                ->nullable();

            $table->string('title')
                ->nullable();

            $table->text('caption')
                ->nullable();

            $table->foreignId('uploaded_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media_libraries');
    }
};
