<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catatan internal per pelanggan. Pelanggan dikenali dari nomor WhatsApp
     * (sales.buyer_phone, format 62812xxx), jadi tidak butuh tabel pelanggan terpisah.
     * Satu nomor hanya punya satu catatan.
     */
    public function up(): void
    {
        if (Schema::hasTable('customer_notes')) {
            return;
        }

        Schema::create('customer_notes', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 20)->unique();
            $table->text('note')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_notes');
    }
};