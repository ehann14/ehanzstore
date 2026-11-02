<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\CocAccount;
use App\Models\CocAccountImage;
use App\Models\Sale;
use App\Support\Watermark;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use RuntimeException;

/**
 * Halaman titip akun untuk penjual (tanpa login) lewat link sekali pakai dari admin.
 *
 * Penjual mengisi data akun + screenshot. Setelah terkirim, token dihapus sehingga link tidak bisa
 * dibuka lagi. Akun tetap berstatus DRAFT: admin yang meninjau lalu menerbitkannya.
 */
class SellerSubmissionController extends Controller
{
    private const MAX_IMAGES = 10;

    public function show(string $token)
    {
        $account = $this->openAccount($token);

        if (! $account) {
            return $this->closed();
        }

        return view('public.seller-submission', ['state' => 'form', 'account' => $account, 'token' => $token]);
    }

    public function submit(Request $request, string $token)
    {
        // Jebakan bot: kolom ini disembunyikan dari manusia
        if ($request->filled('website')) {
            return $this->closed();
        }

        $account = $this->openAccount($token);

        if (! $account) {
            return $this->closed();
        }

        $request->merge(['player_tag' => CocAccount::normalizeTag($request->input('player_tag'))]);

        $data = $request->validate($this->rules(), $this->messages());

        try {
            DB::transaction(function () use ($token, $request, $data) {
                // Kunci baris & cek ulang supaya link tidak bisa dipakai dua kali (klik ganda / dua tab)
                $account = CocAccount::lockForUpdate()
                    ->where('submission_token', $token)
                    ->where('submission_status', CocAccount::SUBMISSION_PENDING)
                    ->first();

                if (! $account || ! $account->sellerLinkIsOpen()) {
                    throw new RuntimeException('closed');
                }

                $stored = $this->storeImages($request->file('images', []), $account);

                if ($stored < 1) {
                    throw new RuntimeException('images');
                }

                $protected = $data['protection_status'] === 'Protected';

                $account->update([
                    'consignor_name'          => trim(strip_tags($data['consignor_name'])),
                    'consignor_phone'         => Sale::normalizePhone($data['consignor_phone']),
                    'price'                   => $data['price'],
                    'town_hall'               => (int) $data['town_hall'],
                    'player_tag'              => $data['player_tag'] ?? null,
                    'account_level'           => (int) ($data['account_level'] ?? 0),
                    'builder_count'           => (int) ($data['builder_count'] ?? 5),
                    'gems'                    => (int) ($data['gems'] ?? 0),
                    'cn_information'          => filled($data['cn_information'] ?? null) ? strip_tags($data['cn_information']) : null,
                    'barbarian_king_level'    => (int) ($data['barbarian_king_level'] ?? 0),
                    'archer_queen_level'      => (int) ($data['archer_queen_level'] ?? 0),
                    'minion_prince_level'     => (int) ($data['minion_prince_level'] ?? 0),
                    'grand_warden_level'      => (int) ($data['grand_warden_level'] ?? 0),
                    'royal_champion_level'    => (int) ($data['royal_champion_level'] ?? 0),
                    'dragon_duke_level'       => (int) ($data['dragon_duke_level'] ?? 0),
                    'protection_status'       => $data['protection_status'],
                    // penjual sudah menyatakan Gmail (dan nomor proteksi bila ON) bisa diganti ke pembeli
                    'email_change_status'     => 'Changeable',
                    'email_change_available_at' => null,
                    'consignor_email_ok'      => true,
                    'consignor_phone_ok'      => $protected,
                    'description'             => filled($data['description'] ?? null) ? strip_tags($data['description']) : null,
                    // link mati: token dihapus, status menunggu ditinjau admin
                    'submission_status'       => CocAccount::SUBMISSION_SUBMITTED,
                    'submitted_at'            => now(),
                    'submission_token'        => null,
                    'submission_expires_at'   => null,
                ]);

                ActivityLog::recordFor($account->created_by, 'seller_submit', $account, 'Akun ' . $account->account_code, [
                    'seller' => $account->consignor_name,
                    'photos' => $stored,
                ]);
            });
        } catch (RuntimeException $e) {
            if ($e->getMessage() === 'closed') {
                return $this->closed();
            }

            return back()->withInput()->withErrors([
                'images' => 'Foto gagal disimpan di server. Coba unggah ulang, atau hubungi admin kalau masih gagal.',
            ]);
        }

        return view('public.seller-submission', ['state' => 'done']);
    }

    /** Akun yang link-nya masih terbuka: belum diisi dan belum kedaluwarsa. */
    private function openAccount(string $token): ?CocAccount
    {
        if (strlen($token) < 20 || strlen($token) > 64) {
            return null;
        }

        $account = CocAccount::where('submission_token', $token)
            ->where('submission_status', CocAccount::SUBMISSION_PENDING)
            ->first();

        return $account && $account->sellerLinkIsOpen() ? $account : null;
    }

    private function closed()
    {
        return response()->view('public.seller-submission', ['state' => 'closed'], 410);
    }

    /** Simpan foto (dengan watermark toko). Mengembalikan jumlah foto yang berhasil disimpan. */
    private function storeImages(array $files, CocAccount $account): int
    {
        $order  = (int) $account->images()->max('sort_order');
        $stored = 0;

        foreach ($files as $file) {
            try {
                $path = Watermark::store($file, 'accounts');
            } catch (\Throwable $e) {
                report($e);
                continue;
            }

            $order++;
            CocAccountImage::create([
                'coc_account_id' => $account->id,
                'image_path'     => $path,
                'is_primary'     => $stored === 0 && ! $account->images()->where('is_primary', true)->exists(),
                'sort_order'     => $order,
            ]);
            $stored++;
        }

        return $stored;
    }

    private function rules(): array
    {
        $hero = 'nullable|integer|min:0|max:100';

        return [
            'consignor_name'  => ['required', 'string', 'min:2', 'max:100'],
            'consignor_phone' => [
                'required', 'string', 'max:25',
                function (string $attribute, mixed $value, \Closure $fail) {
                    $phone = Sale::normalizePhone($value);

                    if (! $phone || ! preg_match('/^628[0-9]{8,12}$/', $phone)) {
                        $fail('Nomor WhatsApp tidak valid. Contoh: 0812-3456-7890.');
                    }
                },
            ],
            'price'                => 'required|numeric|min:1000|max:999999999',
            'town_hall'            => 'required|integer|min:1|max:18',
            'player_tag'           => ['nullable', 'string', 'max:20', 'regex:/^#[0-9A-Z]{3,19}$/'],
            'account_level'        => 'nullable|integer|min:0|max:500',
            'builder_count'        => 'nullable|integer|min:0|max:6',
            'gems'                 => 'nullable|integer|min:0|max:99999999',
            'cn_information'       => 'nullable|string|max:255',
            'barbarian_king_level' => $hero,
            'archer_queen_level'   => $hero,
            'minion_prince_level'  => $hero,
            'grand_warden_level'   => $hero,
            'royal_champion_level' => $hero,
            'dragon_duke_level'    => $hero,
            'protection_status'    => ['required', Rule::in(['Protected', 'Unprotected'])],
            'consignor_email_ok'   => 'accepted',
            'consignor_phone_ok'   => 'accepted_if:protection_status,Protected',
            'agree'                => 'accepted',
            'description'          => 'nullable|string|max:3000',
            'images'               => 'required|array|min:1|max:' . self::MAX_IMAGES,
            // 5 MB per foto; foto dari browser sudah dikompres otomatis
            'images.*'             => 'image|mimes:jpeg,png,jpg,webp|max:5120',
        ];
    }

    private function messages(): array
    {
        return [
            'consignor_name.required'  => 'Nama kamu wajib diisi.',
            'consignor_name.min'       => 'Nama minimal 2 karakter.',
            'consignor_phone.required' => 'Nomor WhatsApp wajib diisi.',
            'price.required'           => 'Harga yang kamu inginkan wajib diisi.',
            'price.numeric'            => 'Harga harus berupa angka.',
            'price.min'                => 'Harga minimal Rp 1.000.',
            'price.max'                => 'Harga terlalu besar (maksimal Rp 999.999.999).',
            'town_hall.required'       => 'Level Town Hall wajib diisi.',
            'town_hall.min'            => 'Town Hall minimal 1.',
            'town_hall.max'            => 'Town Hall maksimal 18.',
            'player_tag.regex'         => 'Player tag tidak valid. Contoh: #P2LQ8Y0.',
            'protection_status.required' => 'Pilih status proteksi akun.',
            'consignor_email_ok.accepted' => 'Syarat wajib: centang konfirmasi bahwa Gmail akun bisa diganti ke Gmail pembeli.',
            'consignor_phone_ok.accepted_if' => 'Karena proteksi ON, centang konfirmasi bahwa nomor proteksi bisa diganti ke nomor pembeli.',
            'agree.accepted'           => 'Kamu harus menyetujui syarat titip akun.',
            'description.max'          => 'Deskripsi maksimal 3000 karakter.',
            'images.required'          => 'Unggah minimal 1 screenshot akun.',
            'images.min'               => 'Unggah minimal 1 screenshot akun.',
            'images.max'               => 'Maksimal ' . self::MAX_IMAGES . ' foto.',
            'images.*.image'           => 'File harus berupa gambar.',
            'images.*.mimes'           => 'Format foto harus JPG, PNG, atau WEBP.',
            'images.*.max'             => 'Ukuran satu foto maksimal 5 MB.',
            'images.*.uploaded'        => 'Foto gagal diunggah. Coba lagi dengan foto yang lebih kecil.',
        ];
    }
}