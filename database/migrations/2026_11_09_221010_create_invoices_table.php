<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('number', 40)->unique()->comment('Public invoice reference (ULID)');
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('appointment_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('amount')->comment('Total in Toman');
            $table->string('currency', 3)->default('IRT');
            $table->string('status', 20)->default('draft');
            $table->json('snapshot')->comment('Line items frozen at issue time');
            $table->dateTime('issued_at')->nullable();
            $table->dateTime('paid_at')->nullable();
            $table->dateTime('due_at')->nullable()->comment('Payment deadline');
            $table->timestamps();

            $table->index(['business_id', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
