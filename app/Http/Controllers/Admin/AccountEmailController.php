<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountEmail;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Halaman khusus admin untuk mendaftarkan email (Gmail) yang dipakai di akun.
 * Tidak ada satu pun bagian dari halaman ini yang tampil ke pembeli.
 */
class AccountEmailController extends Controller
{
    private const MAX_PER_SUBMIT = 100;

    public function index(Request $request)
    {
        $user   = Auth::user();
        $base   = fn () => AccountEmail::visibleTo($user);
        $search = trim((string) $request->query('q', ''));
        $status = in_array($request->query('status'), ['free', 'used'], true) ? $request->query('status') : 'all';

        $emails = $base()
            ->with('account:id,account_code,name,account_email_id,sale_status,listing_type')
            ->when($search !== '', fn ($q) => $q->where(function ($w) use ($search) {
                $w->where('email', 'like', "%{$search}%")->orWhere('note', 'like', "%{$search}%");
            }))
            ->when($status === 'free', fn ($q) => $q->free())
            ->when($status === 'used', fn ($q) => $q->used())
            ->orderBy('email')
            ->paginate(20)
            ->withQueryString();

        $total = $base()->count();
        $free  = $base()->free()->count();

        return view('admin.account-emails.index', [
            'emails' => $emails,
            'search' => $search,
            'status' => $status,
            'total'  => $total,
            'free'   => $free,
            'used'   => $total - $free,
        ]);
    }

    /**
     * Tambah email. Satu baris satu email, boleh diberi catatan setelah tanda "|":
     *   ferhan142009@gmail.com | email utama
     */
    public function store(Request $request)
    {
        $request->validate([
            'emails' => 'required|string|max:20000',
        ], [
            'emails.required' => 'Isi minimal satu email.',
        ]);

        $lines = preg_split('/\R+/', trim((string) $request->input('emails'))) ?: [];
        $lines = array_values(array_filter(array_map('trim', $lines), fn ($l) => $l !== ''));

        if (count($lines) > self::MAX_PER_SUBMIT) {
            return back()->withInput()->with('error', 'Maksimal ' . self::MAX_PER_SUBMIT . ' email sekali tambah.');
        }

        // Kunci duplikat semua email yang sudah ada (tabel kecil, cukup dibaca sekali)
        $known = AccountEmail::pluck('email')
            ->mapWithKeys(fn ($e) => [AccountEmail::duplicateKey($e) => true])
            ->all();

        $added   = 0;
        $dupes   = [];
        $invalid = [];

        foreach ($lines as $line) {
            [$rawEmail, $note] = array_pad(explode('|', $line, 2), 2, null);

            $email = AccountEmail::clean($rawEmail);
            $note  = $note !== null ? mb_substr(trim($note), 0, 255) : null;

            if (mb_strlen($email) > 191 || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $invalid[] = $email !== '' ? $email : $line;
                continue;
            }

            $key = AccountEmail::duplicateKey($email);

            if (isset($known[$key])) {
                $dupes[] = $email;
                continue;
            }

            AccountEmail::create([
                'email'      => $email,
                'note'       => $note !== '' ? $note : null,
                'created_by' => Auth::id(),
            ]);

            $known[$key] = true;
            $added++;
        }

        if ($added > 0) {
            ActivityLog::record('add_account_email', null, $added . ' email akun');
        }

        $skipped = [];
        if ($dupes)   { $skipped[] = count($dupes) . ' sudah terdaftar (' . implode(', ', array_slice($dupes, 0, 5)) . (count($dupes) > 5 ? ', ...' : '') . ')'; }
        if ($invalid) { $skipped[] = count($invalid) . ' format salah (' . implode(', ', array_slice($invalid, 0, 5)) . (count($invalid) > 5 ? ', ...' : '') . ')'; }

        $redirect = redirect()->route('admin.account-emails.index');

        if ($skipped) {
            $redirect->with('error', 'Dilewati: ' . implode('; ', $skipped) . '.');
        }

        return $added > 0
            ? $redirect->with('success', $added . ' email ditambahkan.')
            : $redirect->withInput()->with('error', 'Tidak ada email yang ditambahkan. ' . implode('; ', $skipped));
    }

    public function update(Request $request, AccountEmail $accountEmail)
    {
        $this->authorizeEmail($accountEmail);

        $data = $request->validate([
            'email' => ['required', 'string', 'email', 'max:191'],
            'note'  => ['nullable', 'string', 'max:255'],
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email'    => 'Format email tidak valid. Contoh: namaakun@gmail.com.',
            'email.max'      => 'Email maksimal 191 karakter.',
            'note.max'       => 'Catatan maksimal 255 karakter.',
        ]);

        $email = AccountEmail::clean($data['email']);
        $key   = AccountEmail::duplicateKey($email);

        $exists = AccountEmail::where('id', '!=', $accountEmail->id)
            ->pluck('email')
            ->contains(fn ($e) => AccountEmail::duplicateKey($e) === $key);

        if ($exists) {
            return back()->with('error', 'Email itu sudah terdaftar.');
        }

        $accountEmail->update([
            'email' => $email,
            'note'  => filled($data['note'] ?? null) ? trim($data['note']) : null,
        ]);

        ActivityLog::record('update_account_email', null, 'Email akun diperbarui');

        return back()->with('success', 'Email diperbarui.');
    }

    public function destroy(AccountEmail $accountEmail)
    {
        $this->authorizeEmail($accountEmail);

        if ($holder = $accountEmail->holder()) {
            return back()->with('error', 'Email ini sedang dipakai akun ' . $holder->account_code . '. Ganti email akun itu dulu, atau tunggu sampai akunnya terjual.');
        }

        $accountEmail->delete();

        ActivityLog::record('delete_account_email', null, 'Email akun dihapus');

        return back()->with('success', 'Email dihapus dari daftar.');
    }

    /** Admin biasa hanya boleh mengubah email yang ia daftarkan sendiri. */
    private function authorizeEmail(AccountEmail $email): void
    {
        $user = Auth::user();

        if (! $user->isSuperAdmin() && (int) $email->created_by !== (int) $user->id) {
            abort(403);
        }
    }
}