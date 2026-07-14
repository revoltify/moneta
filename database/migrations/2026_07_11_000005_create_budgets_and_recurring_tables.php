<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('period')->default('monthly');
            $table->unsignedBigInteger('amount');
            $table->unsignedTinyInteger('alert_threshold')->default(80);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'category_id', 'period']);
        });

        Schema::create('recurring_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type');
            $table->foreignId('wallet_id')->constrained()->restrictOnDelete();
            $table->foreignId('counter_wallet_id')->nullable()->constrained('wallets')->restrictOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('amount');
            $table->char('currency', 3)->default('BDT');
            $table->string('description')->nullable();
            $table->string('frequency');
            $table->unsignedTinyInteger('interval')->default(1);
            $table->unsignedTinyInteger('day_of_month')->nullable();
            $table->date('starts_on');
            $table->date('ends_on')->nullable();
            $table->date('next_run_on');
            $table->date('last_run_on')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'next_run_on']);
            $table->index(['company_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_transactions');
        Schema::dropIfExists('budgets');
    }
};
