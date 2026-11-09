<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->boolean('payment_required')->default(false)->after('is_active')
                ->comment('Bookings must be paid online before they are confirmed');
            $table->unsignedSmallInteger('payment_deadline_minutes')->default(15)->after('payment_required')
                ->comment('How long an unpaid booking holds its slot');

            $table->index('payment_required');
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropIndex(['payment_required']);
            $table->dropColumn(['payment_required', 'payment_deadline_minutes']);
        });
    }
};
