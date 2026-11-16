<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            // Occurrences of one standing booking share a token, so the
            // tracking page can list the siblings without a schema-level
            // parent table. Null for ordinary, one-off bookings.
            $table->string('series_token', 36)->nullable()->after('notes');
            $table->index('series_token');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex(['series_token']);
            $table->dropColumn('series_token');
        });
    }
};
