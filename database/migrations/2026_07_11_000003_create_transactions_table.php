<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->foreignId('wallet_id')->constrained()->restrictOnDelete();
            $table->foreignId('counter_wallet_id')->nullable()->constrained('wallets')->restrictOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('amount');
            $table->char('currency', 3)->default('BDT');
            $table->date('date');
            $table->string('description')->nullable();
            $table->string('reference')->nullable();
            $table->string('status')->default('posted');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'date']);
            $table->index(['company_id', 'category_id']);
            $table->index(['company_id', 'wallet_id']);
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'type']);
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE transactions ADD CONSTRAINT chk_transactions_positive_amount CHECK (amount > 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
