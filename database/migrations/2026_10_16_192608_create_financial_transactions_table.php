<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('financial_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('type')->comment('revenue or expense');
            $table->string('category');
            $table->string('description');
            $table->integer('amount_cents')->comment('Amount in cents, negative for expenses');
            $table->string('reference_type')->nullable()->comment('e.g. pirep, manual');
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->timestamps();

            $table->index('type');
            $table->index('category');
            $table->index('reference_type');
            $table->index('reference_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_transactions');
    }
};
