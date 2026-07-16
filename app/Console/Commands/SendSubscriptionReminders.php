<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Subscription;
use App\Mail\SubscriptionReminderMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SendSubscriptionReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send renewal reminder emails to church admins 3 days before expiration';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting subscription reminders processing...');
        Log::info('Console: SendSubscriptionReminders command initiated.');

        // Find subscriptions expiring in exactly 3 days
        $targetDate = Carbon::now()->addDays(3)->format('Y-m-d');
        
        $subscriptions = Subscription::where('status', 'active')
            ->whereDate('end_date', $targetDate)
            ->get();

        $count = 0;

        foreach ($subscriptions as $subscription) {
            $church = $subscription->church;
            $user = $church ? $church->users()->first() : null;

            if ($user && $church) {
                try {
                    Mail::to($user->email)->send(new SubscriptionReminderMail(
                        $user->first_name . ' ' . $user->last_name,
                        $church->church_name,
                        $subscription->end_date,
                        $subscription->amount
                    ));
                    
                    $this->info("Reminder sent to {$user->email} for {$church->church_name}.");
                    Log::info("Renewal reminder sent successfully to {$user->email} for subscription ID: {$subscription->id}");
                    $count++;
                } catch (\Exception $e) {
                    $this->error("Failed to send reminder to {$user->email}: " . $e->getMessage());
                    Log::error("Failed to send subscription renewal reminder to {$user->email}: " . $e->getMessage());
                }
            }
        }

        $this->info("Completed. Sent {$count} reminder(s).");
        Log::info("Console: SendSubscriptionReminders completed. Total reminders sent: {$count}");
    }
}
