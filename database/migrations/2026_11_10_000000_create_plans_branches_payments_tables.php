<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->decimal('price_per_branch', 10, 3); // BHD per branch per month
            $table->json('features');
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
        });

        Schema::table('merchants', function (Blueprint $table) {
            $table->foreignId('plan_id')->nullable()->after('timezone')->constrained()->nullOnDelete();
            $table->string('status')->default('trial')->after('plan_id');
            $table->timestamp('paid_until')->nullable()->after('trial_ends_at');
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 10, 3);
            $table->unsignedTinyInteger('months');
            $table->string('reference')->nullable();
            $table->date('paid_on');
            $table->text('note')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');

        Schema::table('merchants', function (Blueprint $table) {
            $table->dropConstrainedForeignId('plan_id');
            $table->dropColumn(['status', 'paid_until']);
        });

        Schema::dropIfExists('branches');
        Schema::dropIfExists('plans');
    }
};
