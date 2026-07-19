<?php

use App\Console\Commands\DataCleanup;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ── Data Cleanup per retention policy (#2 Bulan 3) ──
// Jalankan secara manual: php artisan data:cleanup
// Untuk dry run: php artisan data:cleanup (tanpa --force)
// Schedule ada di bootstrap/app.php -> withSchedule()
Artisan::command('data:cleanup', function () {
    $this->call(DataCleanup::class);
})->purpose('Data cleanup per retention policy (GDPR)');
