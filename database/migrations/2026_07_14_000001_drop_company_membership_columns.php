<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('company_members');

        if (Schema::hasColumn('companies', 'is_personal')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->dropColumn('is_personal');
            });
        }
    }

    public function down(): void
    {
        //
    }
};
