<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The reminder command and the waitlist-promotion job run on the queue worker,
 * where `app()->getLocale()` is the CLI default (fa) — not the locale the
 * customer actually booked in. Pin the locale on the row itself so the worker
 * can restore it faithfully.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->string('locale', 8)->default('fa')->after('customer_phone');
        });

        Schema::table('waitlist_entries', function (Blueprint $table) {
            $table->string('locale', 8)->default('fa')->after('customer_phone');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn('locale');
        });

        Schema::table('waitlist_entries', function (Blueprint $table) {
            $table->dropColumn('locale');
        });
    }
};
