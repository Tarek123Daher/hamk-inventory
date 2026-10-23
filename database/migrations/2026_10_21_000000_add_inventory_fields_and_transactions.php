<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('viewer')->index();
        });

        Schema::table('components', function (Blueprint $table) {
            $table->string('model')->nullable();
            $table->unsignedInteger('quantity')->default(0);
            $table->unsignedInteger('damaged_quantity')->default(0);
            $table->unsignedInteger('lost_quantity')->default(0);
            $table->unsignedInteger('reorder_threshold')->default(0);
            $table->boolean('is_active')->default(true)->index();
        });

        Schema::table('kits', function (Blueprint $table) {
            $table->string('location')->nullable();
        });

        Schema::create('borrowings', function (Blueprint $table) {
            $table->id();
            $table->string('borrower_name');
            $table->string('borrower_email')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->date('borrowed_at');
            $table->date('expected_return_at')->nullable();
            $table->date('returned_at')->nullable();
            $table->string('status')->default('borrowed')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('borrowing_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('borrowing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('component_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('kit_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('returned_quantity')->default(0);
            $table->timestamps();
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('component_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type')->index();
            $table->integer('quantity');
            $table->string('source')->nullable();
            $table->string('destination')->nullable();
            $table->text('reason')->nullable();
            $table->timestamps();
            $table->index(['component_id', 'created_at']);
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->text('description');
            $table->timestamps();
            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('borrowing_items');
        Schema::dropIfExists('borrowings');
        Schema::table('kits', fn (Blueprint $table) => $table->dropColumn('location'));
        Schema::table('components', fn (Blueprint $table) => $table->dropColumn([
            'model', 'quantity', 'damaged_quantity', 'lost_quantity', 'reorder_threshold', 'is_active',
        ]));
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('role'));
    }
};
