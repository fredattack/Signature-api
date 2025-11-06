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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
            $table->string('stripe_subscription_id')->unique()->nullable();
            $table->enum('status', ['active', 'cancelled', 'past_due', 'unpaid', 'incomplete'])->default('active');
            $table->enum('plan_type', ['monthly', 'annual'])->default('monthly');
            $table->timestamp('current_period_start');
            $table->timestamp('current_period_end');
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('stripe_subscription_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
