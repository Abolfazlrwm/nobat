<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discount_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('code', 40)->comment('The code customers type at checkout');
            $table->string('type', 10)->default('percent')->comment('percent | fixed');
            $table->unsignedInteger('value')->comment('Percent (1-100) or a fixed IRT amount');
            $table->unsignedInteger('min_amount')->nullable()->comment('Minimum service price the code accepts');
            $table->unsignedInteger('max_uses')->nullable()->comment('Total usage cap across all customers');
            $table->unsignedInteger('used_count')->default(0);
            $table->unsignedInteger('per_user_limit')->nullable()->comment('Usage cap per customer phone');
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['business_id', 'code']);
            $table->index(['business_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discount_codes');
    }
};
