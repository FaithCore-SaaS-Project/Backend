<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:reset-monthly-sms')]
#[Description('Command description')]
class ResetMonthlySms extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
    }
}
