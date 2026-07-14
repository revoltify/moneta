<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type');
            $table->string('account_number')->nullable();
            $table->string('icon')->nullable();
            $table->string('color')->nullable();
            $table->char('currency', 3)->default('BDT');
            $table->bigInteger('opening_balance')->default(0);
            $table->bigInteger('cached_balance')->default(0);
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'name']);
            $table->index(['company_id', 'archived_at']);
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('kind');
            $table->string('name');
            $table->string('icon')->nullable();
            $table->string('color')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'parent_id', 'kind', 'name']);
            $table->index(['company_id', 'kind', 'archived_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
        Schema::dropIfExists('wallets');
    }
};
