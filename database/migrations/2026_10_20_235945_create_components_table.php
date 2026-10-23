<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('components', function (Blueprint $table) {
            $table->id();
            $table->string('sku')->nullable()->unique();
            $table->string('name');
            $table->foreignId('category_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('manufacturer')->nullable();
            $table->string('image_path')->nullable();
            $table->string('datasheet_url')->nullable();
            $table->json('attributes')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('components');
    }
};