<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Ulasan pembeli. Satu transaksi hanya boleh punya satu ulasan.
     * Ulasan baru berstatus "pending" dan baru tampil di publik setelah
     * disetujui super admin.
     *
     * Migrasi ini aman dijalankan ulang: bagian yang sudah ada dilewati,
     * dan tabel reviews dengan struktur lama/salah (tanpa kolom status)
     * dibuat ulang selama masih kosong.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('sales', 'review_token')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->string('review_token', 64)->nullable()->unique()->after('notes');
            });
        }

        // Transaksi lama ikut dibuatkan token supaya link ulasannya bisa dikirim.
        DB::table('sales')->whereNull('review_token')->orderBy('id')->each(function ($sale) {
            DB::table('sales')->where('id', $sale->id)->update(['review_token' => Str::random(40)]);
        });

        if (Schema::hasTable('reviews') && ! Schema::hasColumn('reviews', 'status')) {
            if (DB::table('reviews')->count() > 0) {
                throw new RuntimeException(
                    'Tabel reviews sudah ada dengan struktur berbeda dan berisi data. ' .
                    'Cadangkan lalu hapus tabelnya secara manual, kemudian jalankan migrate lagi.'
                );
            }

            Schema::drop('reviews');
        }

        if (! Schema::hasTable('reviews')) {
            Schema::create('reviews', function (Blueprint $table) {
                $table->id();
                $table->foreignId('sale_id')->unique()->constrained('sales')->cascadeOnDelete();
                $table->foreignId('coc_account_id')->constrained('coc_accounts');
                $table->string('reviewer_name', 60);
                $table->unsignedTinyInteger('rating');
                $table->text('comment');
                $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
                $table->foreignId('moderated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('moderated_at')->nullable();
                $table->timestamps();

                $table->index(['coc_account_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');

        if (Schema::hasColumn('sales', 'review_token')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->dropUnique(['review_token']);
                $table->dropColumn('review_token');
            });
        }
    }
};