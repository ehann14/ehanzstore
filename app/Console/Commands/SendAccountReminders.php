<?php

namespace App\Console\Commands;

use App\Support\AccountReminders;
use Illuminate\Console\Command;

class SendAccountReminders extends Command
{
    protected $signature = 'accounts:remind';

    protected $description = 'Kirim notifikasi: ss ulang spek sebelum akun tayang otomatis & Gmail akun sudah bisa diganti';

    public function handle(): int
    {
        $r = AccountReminders::run();

        $this->info("Pengingat ss spek: {$r['screenshot']}, Gmail bisa diganti: {$r['email']}");

        return self::SUCCESS;
    }
}