<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_code', 20)->unique();
            $table->string('token', 64)->unique();
            $table->unsignedBigInteger('coc_account_id')->index();
            $table->string('account_code_snapshot');
            $table->string('account_name_snapshot');
            $table->string('buyer_name', 100);
            $table->string('buyer_phone', 20);
            $table->decimal('price_snapshot', 12, 2);
            $table->unsignedTinyInteger('dp_percent')->default(20);
            $table->decimal('dp_amount', 12, 2);
            // pending | active | completed | cancelled | expired
            $table->string('status', 20)->default('pending')->index();
            // unpaid | held | applied | forfeited | refunded
            $table->string('dp_state', 20)->default('unpaid');
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('dp_paid_at')->nullable();
            $table->unsignedBigInteger('confirmed_by')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancel_reason')->nullable();
            $table->unsignedBigInteger('sale_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('terms_accepted_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};