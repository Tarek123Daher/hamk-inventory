<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('components', function (Blueprint $table) {
            $table->unsignedInteger('verified_quantity')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->text('verification_notes')->nullable();
        });

        Schema::create('inventory_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('filename');
            $table->char('sha256', 64)->unique();
            $table->unsignedInteger('inserted')->default(0);
            $table->unsignedInteger('updated')->default(0);
            $table->unsignedInteger('skipped')->default(0);
            $table->unsignedInteger('errors')->default(0);
            $table->json('error_details')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_imports');
        Schema::table('components', function (Blueprint $table) {
            $table->dropColumn(['verified_quantity', 'verified_at', 'verification_notes']);
        });
    }
};
