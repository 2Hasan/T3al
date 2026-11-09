<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // المحل (العميل اللي يدفع الاشتراك) — هو الـ tenant في لوحة Filament
        Schema::create('merchants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('logo_path')->nullable();
            $table->string('timezone')->default('Asia/Bahrain');
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamps();
        });

        // ربط المستخدمين بالمحلات: صاحب المحل (owner) أو كاشير (staff)
        Schema::create('merchant_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role')->default('staff');
            $table->timestamps();
            $table->unique(['merchant_id', 'user_id']);
        });

        // برنامج الولاء (قالب البطاقة): كم ختم، وش المكافأة، والألوان
        Schema::create('loyalty_programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedSmallInteger('stamps_required')->default(10);
            $table->string('reward_label');
            $table->string('background_color', 7)->default('#1F2937');
            $table->string('foreground_color', 7)->default('#FFFFFF');
            $table->string('label_color', 7)->default('#D1D5DB');
            $table->text('terms')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // عملاء المحل (كل محل عنده قائمة عملائه الخاصة)
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('phone');
            $table->date('birthday')->nullable();
            $table->timestamps();
            $table->unique(['merchant_id', 'phone']);
        });

        // بطاقة العميل في برنامج معيّن
        Schema::create('loyalty_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('loyalty_program_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('code')->unique(); // القيمة اللي داخل الـ QR
            $table->unsignedSmallInteger('stamps')->default(0);
            $table->unsignedSmallInteger('rewards_available')->default(0);
            $table->unsignedInteger('lifetime_stamps')->default(0);
            $table->unsignedInteger('rewards_redeemed')->default(0);
            $table->timestamp('last_visit_at')->nullable();
            $table->timestamps();
            $table->unique(['loyalty_program_id', 'customer_id']);
        });

        // سجل كل عملية: مين ختم، متى، وفي أي محل
        Schema::create('card_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('loyalty_card_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type'); // stamp | redeem
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->timestamp('created_at')->useCurrent();
            $table->index(['merchant_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('card_activities');
        Schema::dropIfExists('loyalty_cards');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('loyalty_programs');
        Schema::dropIfExists('merchant_user');
        Schema::dropIfExists('merchants');
    }
};
