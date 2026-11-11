<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->foreignId('discount_code_id')->nullable()->after('notes')->constrained()->nullOnDelete();
            $table->unsignedInteger('discount_amount')->nullable()->after('discount_code_id')->comment('IRT taken off the service price');

            $table->index('discount_code_id');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex(['discount_code_id']);
            $table->dropConstrainedForeignId('discount_code_id');
            $table->dropColumn('discount_amount');
        });
    }
};
