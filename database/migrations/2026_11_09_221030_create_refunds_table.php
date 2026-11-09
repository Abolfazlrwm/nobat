<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('amount')->comment('Refunded amount in Toman');
            $table->string('status', 20)->default('pending');
            $table->string('reason')->nullable();
            $table->string('gateway_refund_id', 64)->nullable();
            $table->dateTime('processed_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['payment_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refunds');
    }
};
