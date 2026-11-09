<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->string('gateway', 20)->default('zarinpal');
            $table->unsignedInteger('amount')->comment('Amount in Toman');
            $table->string('status', 20)->default('initiated');
            $table->string('idempotency_key', 64)->unique()
                ->comment('Client-side dedupe key generated at initiation');
            $table->string('authority', 100)->nullable()->unique()
                ->comment('Gateway transaction id; unique index = one payment per gateway session');
            $table->string('ref_id', 40)->nullable();
            $table->string('card_pan', 24)->nullable()->comment('Masked card number returned by the gateway');
            $table->text('error_message')->nullable();
            $table->dateTime('paid_at')->nullable();
            $table->dateTime('verified_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['appointment_id', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
