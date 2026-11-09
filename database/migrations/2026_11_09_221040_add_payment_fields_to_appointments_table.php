<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dateTime('payment_expires_at')->nullable()->after('status')
                ->comment('When set, the slot is held only until this moment');
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->index('payment_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex(['payment_expires_at']);
            $table->dropColumn('payment_expires_at');
        });
    }
};
