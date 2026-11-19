<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $table->string('name', 60);
            $table->string('type', 20);
            $table->unsignedTinyInteger('multiplier')->nullable();
            $table->json('weekdays')->nullable();
            $table->unsignedSmallInteger('inactive_days')->nullable();
            $table->unsignedTinyInteger('bonus_stamps')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['merchant_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offers');
    }
};
