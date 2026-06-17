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
        Schema::create('download_category_download', function (Blueprint $table) {

            $table->foreignId('download_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('download_category_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->primary([
                'download_id',
                'download_category_id',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('download_category_download');
    }
};
