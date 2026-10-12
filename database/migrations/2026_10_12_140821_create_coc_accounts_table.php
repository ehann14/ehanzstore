<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coc_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('account_code')->unique(); // Kode unik otomatis
            $table->string('name');
            $table->integer('town_hall'); // TH 1-16
            $table->integer('account_level')->default(0);
            $table->integer('barbarian_king_level')->default(0);
            $table->integer('archer_queen_level')->default(0);
            $table->integer('grand_warden_level')->default(0);
            $table->integer('royal_champion_level')->default(0);
            $table->integer('builder_count')->default(5);
            $table->integer('gems')->default(0);
            $table->text('cn_information')->nullable(); // Clan Name / Info tambahan
            $table->enum('protection_status', ['Protected', 'Unprotected'])->default('Unprotected');
            $table->enum('email_change_status', ['Original', 'Changed', 'Unknown'])->default('Unknown');
            $table->decimal('price', 12, 2);
            $table->text('description')->nullable();
            
            // Status Logic
            $table->enum('publication_status', ['draft', 'published'])->default('draft');
            $table->enum('sale_status', ['available', 'sold', 'archived'])->default('available');
            
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coc_accounts');
    }
};