<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('title');                                   // judul internal / alt gambar
            $table->string('image_path');                              // disimpan di disk "public"
            $table->enum('link_type', ['none', 'whatsapp', 'instagram', 'custom'])->default('none');
            $table->string('link_value')->nullable();                  // nomor WA / username IG / URL
            $table->string('link_url')->nullable();                    // URL final hasil olahan
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banners');
    }
};