<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('waitlist_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->restrictOnDelete();
            $table->foreignId('staff_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->date('desired_date')->comment('The fully booked day the customer hopes for');
            $table->string('customer_name');
            $table->string('customer_phone', 20);
            $table->string('status', 20)->default('waiting')->comment('waiting | notified | booked | cancelled');
            $table->dateTime('notified_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'desired_date', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waitlist_entries');
    }
};
