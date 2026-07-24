<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

    #[Signature('sms:reset-quotas')]
    #[Description('Reset the monthly SMS used count for churches based on their subscription billing date')]
    class ResetSmsQuotas extends Command
    {
        /**
         * Execute the console command.
         */
        public function handle()
        {
            $this->info('Starting SMS quota reset process...');
            
            $today = now();
            $churchesReset = 0;
            
            // Get all active subscriptions
            $subscriptions = \App\Models\Subscription::whereIn('status', ['active', 'trialing'])->get();
            
            foreach ($subscriptions as $sub) {
                $startDate = \Carbon\Carbon::parse($sub->start_date);
                
                // Determine if today is the monthly anniversary
                $isAnniversary = false;
                
                if ($today->day === $startDate->day) {
                    $isAnniversary = true;
                } elseif ($today->isLastOfMonth() && $startDate->day > $today->day) {
                    // Handle edge cases like starting on Jan 31st, and today is Feb 28th
                    $isAnniversary = true;
                }
                
                if ($isAnniversary && $sub->church) {
                    $sub->church->update(['monthly_sms_used' => 0]);
                    $churchesReset++;
                    $this->line("Reset SMS quota for Church ID: {$sub->church_id}");
                }
            }
            
            $this->info("Successfully reset SMS quotas for {$churchesReset} churches.");
        }
    }
