<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('component_kit', function (Blueprint $table) {
            $table->id();

            $table->foreignId('component_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('kit_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->unsignedInteger('quantity')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['component_id', 'kit_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('component_kit');
    }
};