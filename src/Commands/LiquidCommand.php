<?php

namespace Keepsuit\Liquid\Commands;

use Illuminate\Console\Command;

class LiquidCommand extends Command
{
    public $signature = 'laravel-liquid';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
