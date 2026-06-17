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
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');

            $table->string('contact_name');

            $table->string('email');

            $table->string('phone')
                ->nullable();

            $table->longText('notes')
                ->nullable();

            $table->enum('status', [
                'new',
                'processing',
                'quoted',
                'closed'
            ])->default('new');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quotations');
    }
};
