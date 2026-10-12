<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visitor_events', function (Blueprint $table) {
            $table->id();
            $table->string('anonymous_session_id')->index(); // Untuk tracking unique visitor
            $table->string('event_type'); // page_view, click_whatsapp, view_account, search
            $table->string('page_path')->nullable();
            $table->foreignId('coc_account_id')->nullable()->constrained()->nullOnDelete();
            $table->json('event_metadata')->nullable(); // Menyimpan data tambahan seperti query search
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visitor_events');
    }
};