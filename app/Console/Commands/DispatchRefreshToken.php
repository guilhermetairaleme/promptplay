<?php

namespace App\Console\Commands;

use App\Jobs\RefreshTokenJob;
use Illuminate\Console\Command;

class DispatchRefreshToken extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    /**
     * The console command description.
     *
     * @var string
     */

    protected $signature = 'token:refresh-check';
    protected $description = 'Verifica e atualiza o token se necessário.';

    public function handle()
    {
        RefreshTokenJob::dispatch();
    }
}
