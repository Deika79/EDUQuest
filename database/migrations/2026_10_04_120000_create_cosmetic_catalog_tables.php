<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cosmetic_items', function (Blueprint $table) {
            $table->id();
            $table->string('sku', 80)->unique();
            $table->string('name', 120);
            $table->string('character_key', 32);
            $table->string('collection', 40);
            $table->string('asset_path', 255)->unique();
            $table->unsignedSmallInteger('coin_price');
            $table->unsignedTinyInteger('minimum_level');
            $table->boolean('starter')->default(false);
            $table->boolean('active')->default(true);
            $table->unsignedTinyInteger('sort_order');
            $table->timestamps();

            $table->index(['character_key', 'active', 'sort_order']);
        });

        Schema::create('student_cosmetic_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('cosmetic_item_id')->constrained()->restrictOnDelete();
            $table->string('acquisition_type', 24);
            $table->timestamp('acquired_at');
            $table->timestamps();

            $table->unique(['student_id', 'cosmetic_item_id']);
        });

        Schema::table('avatar_profiles', function (Blueprint $table) {
            $table->foreignId('equipped_cosmetic_item_id')
                ->nullable()
                ->after('character_key')
                ->constrained('cosmetic_items')
                ->restrictOnDelete();
        });

        Schema::table('coin_ledger_entries', function (Blueprint $table) {
            $table->foreignId('cosmetic_item_id')
                ->nullable()
                ->after('reward_grant_id')
                ->constrained('cosmetic_items')
                ->restrictOnDelete();
            $table->unique(
                ['student_id', 'cosmetic_item_id', 'reason'],
                'coin_ledger_student_cosmetic_reason_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::table('coin_ledger_entries', function (Blueprint $table) {
            $table->dropUnique('coin_ledger_student_cosmetic_reason_unique');
            $table->dropConstrainedForeignId('cosmetic_item_id');
        });

        Schema::table('avatar_profiles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('equipped_cosmetic_item_id');
        });

        Schema::dropIfExists('student_cosmetic_items');
        Schema::dropIfExists('cosmetic_items');
    }
};
