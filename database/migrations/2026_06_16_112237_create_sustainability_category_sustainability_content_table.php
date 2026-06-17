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
        Schema::create('sustainability_content_category', function (Blueprint $table) {

            $table->unsignedBigInteger('sustainability_content_id');

            $table->unsignedBigInteger('sustainability_category_id');

            $table->foreign('sustainability_content_id', 'scc_content_fk')
                ->references('id')
                ->on('sustainability_contents')
                ->cascadeOnDelete();

            $table->foreign('sustainability_category_id', 'scc_category_fk')
                ->references('id')
                ->on('sustainability_categories')
                ->cascadeOnDelete();

            $table->primary([
                'sustainability_content_id',
                'sustainability_category_id',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sustainability_category_sustainability_content');
    }
};
