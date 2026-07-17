<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class HealthCheck extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'health:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check application health status for Docker healthcheck';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $healthy = true;

        // Check database connection
        try {
            DB::connection()->getPdo();
            $this->info('✓ Database: Connected');
        } catch (\Exception $e) {
            $this->error('✗ Database: ' . $e->getMessage());
            $healthy = false;
        }

        // Check cache (Redis)
        try {
            Cache::store('redis')->put('health:check', true, 1);
            $value = Cache::store('redis')->get('health:check');
            if ($value === true) {
                $this->info('✓ Cache (Redis): Working');
            } else {
                $this->warn('~ Cache: Unexpected value');
            }
        } catch (\Exception $e) {
            $this->warn('~ Cache (Redis): ' . $e->getMessage() . ' (non-critical)');
        }

        return $healthy ? Command::SUCCESS : Command::FAILURE;
    }
}
