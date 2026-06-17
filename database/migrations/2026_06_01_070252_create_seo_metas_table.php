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
        Schema::create('seo_metas', function (Blueprint $table) {

            $table->id();

            $table->morphs('seoable');

            $table->string('meta_title')->nullable();

            $table->text('meta_description')->nullable();

            $table->string('focus_keyword')->nullable();

            $table->string('og_title')->nullable();

            $table->text('og_description')->nullable();

            $table->string('og_image')->nullable();

            $table->string('canonical_url')->nullable();

            $table->string('robots')
                ->default('index,follow');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seo_metas');
    }
};
