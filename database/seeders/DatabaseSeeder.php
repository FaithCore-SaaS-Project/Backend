<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Call Plan & Role Seeders
        $this->call([
            PlanSeeder::class,
            RolePermissionSeeder::class,
        ]);

        // 2. Create the Main Tenant (Church)
        $church = \App\Models\Church::updateOrCreate(
            ['email' => 'contact@beracah.org'],
            [
                'church_name' => 'Beracah Christian Ministry',
                'pastor_name' => 'Pastor John',
                'email' => 'contact@beracah.org',
                'phone' => '+94 77 123 4567',
                'address' => '123 Temple Road, Colombo',
                'city' => 'Colombo',
                'status' => 'active'
            ]
        );

        // 3. Create Subscription
        $premiumPlan = \App\Models\Plan::where('name', 'Premium')->first();
        if ($premiumPlan) {
            \App\Models\Subscription::updateOrCreate(
                ['church_id' => $church->id],
                [
                    'plan_id' => $premiumPlan->id,
                    'start_date' => date('Y-m-d'),
                    'end_date' => date('Y-m-d', strtotime('+1 year')),
                    'amount' => $premiumPlan->price,
                    'status' => 'active'
                ]
            );
        }

        // 4. Create Tenant Administrator User
        $admin = \App\Models\User::updateOrCreate(
            ['email' => 'admin@faithcore.com'],
            [
                'church_id' => $church->id,
                'first_name' => 'Pastor John',
                'last_name' => 'Silva',
                'email' => 'admin@faithcore.com',
                'password' => bcrypt('password'),
                'status' => true
            ]
        );

        // Assign Spatie Role
        $admin->assignRole('Church Administrator');

        // 5. Seed Finance Categories
        $categories = [
            ['name' => 'Tithes', 'type' => 'Income', 'description' => 'Tithes received from members'],
            ['name' => 'Offerings', 'type' => 'Income', 'description' => 'Offerings and thanksgiving gifts'],
            ['name' => 'Donations', 'type' => 'Income', 'description' => 'General donations'],
            ['name' => 'Thanksgiving', 'type' => 'Income', 'description' => 'Thanksgiving collections'],
            ['name' => 'Other Income', 'type' => 'Income', 'description' => 'Miscellaneous income'],
            ['name' => 'Ministry', 'type' => 'Expense', 'description' => 'Expenses related to ministries'],
            ['name' => 'Utilities', 'type' => 'Expense', 'description' => 'Electricity, Water, Internet etc.'],
            ['name' => 'Salaries', 'type' => 'Expense', 'description' => 'Staff salaries and allowances'],
            ['name' => 'Maintenance', 'type' => 'Expense', 'description' => 'Building and equipment maintenance'],
            ['name' => 'Office', 'type' => 'Expense', 'description' => 'Stationery and office expenses'],
            ['name' => 'Others', 'type' => 'Expense', 'description' => 'Other expenditures']
        ];

        foreach ($categories as $cat) {
            \App\Models\FinanceCategory::updateOrCreate(
                ['church_id' => $church->id, 'name' => $cat['name']],
                [
                    'type' => $cat['type'],
                    'description' => $cat['description'],
                    'status' => 'Active',
                    'created_on' => date('Y-m-d'),
                    'created_by' => 'Pastor John'
                ]
            );
        }

        // 6. Seed Bank Accounts
        $accounts = [
            [
                'bank_name' => 'Hatton National Bank',
                'account_name' => 'Main Church Account',
                'account_number' => '1210 1200 1234 567',
                'account_type' => 'Current',
                'balance' => 2145600.00,
                'ledger_balance' => 2145600.00,
                'status' => 'Active',
                'branch' => 'Kandy City Branch',
                'currency' => 'LKR'
            ],
            [
                'bank_name' => 'Commercial Bank',
                'account_name' => 'Building Fund Account',
                'account_number' => '8001 0012 3456',
                'account_type' => 'Savings',
                'balance' => 1250000.00,
                'ledger_balance' => 1250000.00,
                'status' => 'Active',
                'branch' => 'Colombo 03 Branch',
                'currency' => 'LKR'
            ],
            [
                'bank_name' => "People's Bank",
                'account_name' => 'Mission Fund Account',
                'account_number' => '1632 1000 7890',
                'account_type' => 'Savings',
                'balance' => 480750.00,
                'ledger_balance' => 480750.00,
                'status' => 'Active',
                'branch' => 'Jaffna Branch',
                'currency' => 'LKR'
            ]
        ];

        foreach ($accounts as $acc) {
            \App\Models\BankAccount::updateOrCreate(
                ['church_id' => $church->id, 'account_number' => $acc['account_number']],
                array_merge($acc, [
                    'created_on' => date('Y-m-d'),
                    'created_by' => 'Pastor John',
                    'last_statement_date' => date('Y-m-d')
                ])
            );
        }

        // 7. Seed Budgets
        $budgets = [
            [
                'name' => 'Ministry Support',
                'type' => 'Ministry',
                'budget_amount' => 500000.00,
                'spent_amount' => 12500.00,
                'period_start' => date('Y') . '-01-01',
                'period_end' => date('Y') . '-12-31',
                'status' => 'In Progress',
                'description' => 'Budget allocated for support of ministries and missionary outreach.'
            ],
            [
                'name' => 'Building Upkeep & Repairs',
                'type' => 'Capital',
                'budget_amount' => 2000000.00,
                'spent_amount' => 0.00,
                'period_start' => date('Y') . '-01-01',
                'period_end' => date('Y') . '-12-31',
                'status' => 'In Progress',
                'description' => 'Budget for building renovations and sanctuary improvements.'
            ],
            [
                'name' => 'Administration & Bills',
                'type' => 'Operating',
                'budget_amount' => 800000.00,
                'spent_amount' => 18750.00,
                'period_start' => date('Y') . '-01-01',
                'period_end' => date('Y') . '-12-31',
                'status' => 'In Progress',
                'description' => 'General utilities, stationery, salaries and bank charges.'
            ]
        ];

        foreach ($budgets as $b) {
            \App\Models\Budget::updateOrCreate(
                ['church_id' => $church->id, 'name' => $b['name']],
                array_merge($b, [
                    'created_on' => date('Y-m-d')
                ])
            );
        }

        // 8. Seed Sample Finance Transactions
        $incomes = [
            [
                'category' => 'Tithes',
                'amount' => 25000.00,
                'income_date' => date('Y-m-d'),
                'method' => 'Cash',
                'receipt' => 'RCP-2025-1082',
                'description' => 'Sunday Tithe - Saman Perera'
            ],
            [
                'category' => 'Offerings',
                'amount' => 15000.00,
                'income_date' => date('Y-m-d'),
                'method' => 'Cash',
                'receipt' => 'RCP-2025-1081',
                'description' => 'Sunday Offering'
            ],
            [
                'category' => 'Donations',
                'amount' => 50000.00,
                'income_date' => date('Y-m-d'),
                'method' => 'Bank Transfer',
                'receipt' => 'RCP-2025-1080',
                'description' => 'Building Fund Donation'
            ]
        ];

        foreach ($incomes as $inc) {
            \App\Models\FinanceIncome::create(array_merge($inc, [
                'church_id' => $church->id
            ]));
        }

        $expenses = [
            [
                'category' => 'Ministry',
                'amount' => 12500.00,
                'expense_date' => date('Y-m-d', strtotime('-1 day')),
                'method' => 'Bank Transfer',
                'receipt' => 'EXP-2025-0542',
                'description' => 'Youth Program Expenses'
            ],
            [
                'category' => 'Utilities',
                'amount' => 18750.00,
                'expense_date' => date('Y-m-d', strtotime('-1 day')),
                'method' => 'Bank Transfer',
                'receipt' => 'EXP-2025-0541',
                'description' => 'Electricity Bill Payment'
            ]
        ];

        foreach ($expenses as $exp) {
            \App\Models\FinanceExpense::create(array_merge($exp, [
                'church_id' => $church->id
            ]));
        }

        // 9. Seed Default Members
        $members = [
            [
                'member_no' => 'MEM-2025-0001',
                'first_name' => 'John',
                'last_name' => 'Miller',
                'phone' => '+1 (555) 019-2831',
                'email' => 'thomas.miller@gracecommunity.org',
                'gender' => 'male',
                'dob' => '1975-10-12',
                'address' => 'New York, NY, USA',
                'baptism_date' => '2023-03-12',
                'membership_date' => '2015-08-12',
                'occupation' => 'Lead Pastor',
                'status' => true
            ],
            [
                'member_no' => 'MEM-2025-0002',
                'first_name' => 'Saman',
                'last_name' => 'Perera',
                'phone' => '+94 77 987 6543',
                'email' => 'saman@gmail.com',
                'gender' => 'male',
                'dob' => '1990-05-15',
                'address' => 'Kandy, Sri Lanka',
                'baptism_date' => '2023-03-12',
                'membership_date' => '2023-03-12',
                'occupation' => 'Software Engineer',
                'status' => true
            ],
            [
                'member_no' => 'MEM-2025-0003',
                'first_name' => 'Nadeesha',
                'last_name' => 'Perera',
                'phone' => '+94 77 987 6544',
                'email' => 'nadeesha@gmail.com',
                'gender' => 'female',
                'dob' => '1992-08-20',
                'address' => 'Kandy, Sri Lanka',
                'baptism_date' => '2023-03-12',
                'membership_date' => '2023-03-12',
                'occupation' => 'Teacher',
                'status' => true
            ]
        ];

        $seededMembers = [];
        foreach ($members as $m) {
            $seededMembers[] = \App\Models\Member::create(array_merge($m, [
                'church_id' => $church->id
            ]));
        }

        // 10. Seed Default Events
        $events = [
            [
                'event_name' => 'Sunday Worship Service',
                'subtitle' => 'Weekly congregational worship and preaching',
                'type' => 'Worship',
                'event_date' => '2025-05-18 08:00:00',
                'event_time' => '8:00 AM - 10:00 AM',
                'venue' => 'Main Sanctuary',
                'attendees' => 3,
                'max_capacity' => 200,
                'status' => 'Upcoming',
                'organizer' => 'Pastor John',
                'description' => 'Join us for our weekly gathering as we praise God together and hear His word.',
                'created_on' => '2025-05-01'
            ],
            [
                'event_name' => 'Midweek Bible Study',
                'subtitle' => 'Deep dive into the Book of Romans',
                'type' => 'Bible Study',
                'event_date' => '2025-05-14 18:30:00',
                'event_time' => '6:30 PM - 8:00 PM',
                'venue' => 'Fellowship Hall',
                'attendees' => 2,
                'max_capacity' => 50,
                'status' => 'Completed',
                'organizer' => 'Pastor John',
                'description' => 'An interactive study focusing on the theological foundations of our faith.',
                'created_on' => '2025-05-01'
            ],
            [
                'event_name' => 'Youth Fellowship Night',
                'subtitle' => 'Friday Night games, worship, and discussion',
                'type' => 'Youth',
                'event_date' => '2025-05-16 19:00:00',
                'event_time' => '7:00 PM - 9:30 PM',
                'venue' => 'Youth Hall',
                'attendees' => 1,
                'max_capacity' => 100,
                'status' => 'Upcoming',
                'organizer' => 'Pastor John',
                'description' => 'A vibrant fellowship night for high school and university students.',
                'created_on' => '2025-05-01'
            ],
            [
                'event_name' => 'Community Food Drive',
                'subtitle' => 'Distributing essential food packets',
                'type' => 'Outreach',
                'event_date' => '2025-05-17 09:00:00',
                'event_time' => '9:00 AM - 1:00 PM',
                'venue' => 'Church Car Park',
                'attendees' => 3,
                'max_capacity' => 150,
                'status' => 'Ongoing',
                'organizer' => 'Pastor John',
                'description' => 'Reaching out to our local community by providing food packs to families in need.',
                'created_on' => '2025-05-01'
            ]
        ];

        $seededEvents = [];
        foreach ($events as $evt) {
            $seededEvents[] = \App\Models\Event::create(array_merge($evt, [
                'church_id' => $church->id
            ]));
        }

        // 11. Seed Event Registrations (Connect Members to Events)
        foreach ($seededMembers as $member) {
            \App\Models\EventRegistration::create([
                'church_id' => $church->id,
                'event_id' => $seededEvents[0]->id,
                'member_id' => $member->id,
                'status' => 'registered'
            ]);
        }

        \App\Models\EventRegistration::create([
            'church_id' => $church->id,
            'event_id' => $seededEvents[1]->id,
            'member_id' => $seededMembers[0]->id,
            'status' => 'checked_in'
        ]);
        \App\Models\EventRegistration::create([
            'church_id' => $church->id,
            'event_id' => $seededEvents[1]->id,
            'member_id' => $seededMembers[1]->id,
            'status' => 'checked_in'
        ]);

        \App\Models\EventRegistration::create([
            'church_id' => $church->id,
            'event_id' => $seededEvents[2]->id,
            'member_id' => $seededMembers[2]->id,
            'status' => 'registered'
        ]);

        foreach ($seededMembers as $member) {
            \App\Models\EventRegistration::create([
                'church_id' => $church->id,
                'event_id' => $seededEvents[3]->id,
                'member_id' => $member->id,
                'status' => 'checked_in'
            ]);
        }
        // 12. Seed Default Certificates
        \App\Models\Certificate::create([
            'church_id' => $church->id,
            'name' => 'Membership Certificate',
            'type' => 'Membership',
            'recipient' => 'Saman Perera',
            'recipient_email' => 'saman@gmail.com',
            'recipient_phone' => '+94 77 987 6543',
            'issued_date' => date('Y-m-d'),
            'issued_by' => 'Pastor John',
            'status' => 'Issued'
        ]);

        \App\Models\Certificate::create([
            'church_id' => $church->id,
            'name' => 'Baptism Certificate',
            'type' => 'Baptism',
            'recipient' => 'Nadeesha Perera',
            'recipient_email' => 'nadeesha@gmail.com',
            'recipient_phone' => '+94 77 987 6544',
            'issued_date' => date('Y-m-d', strtotime('-1 month')),
            'issued_by' => 'Pastor John',
            'status' => 'Issued'
        ]);

        // 13. Seed Default Letters
        \App\Models\Letter::create([
            'church_id' => $church->id,
            'title' => 'Membership Confirmation',
            'letter_type' => 'Confirmation',
            'recipient' => 'Saman Perera',
            'recipient_email' => 'saman@gmail.com',
            'recipient_phone' => '+94 77 987 6543',
            'issue_date' => date('Y-m-d'),
            'status' => 'Sent',
            'sent_by' => 'Pastor John',
            'content' => "Dear Saman Perera,\n\nWe are pleased to confirm your membership at Beracah Christian Ministry. We look forward to growing together in faith and serving the Lord as one family.\n\nMay God bless you abundantly."
        ]);

        \App\Models\Letter::create([
            'church_id' => $church->id,
            'title' => 'Donation Appreciation',
            'letter_type' => 'Appreciation',
            'recipient' => 'John Miller',
            'recipient_email' => 'thomas.miller@gracecommunity.org',
            'recipient_phone' => '+1 (555) 019-2831',
            'issue_date' => date('Y-m-d', strtotime('-1 week')),
            'status' => 'Sent',
            'sent_by' => 'Pastor John',
            'content' => "Dear John Miller,\n\nOn behalf of Beracah Christian Ministry, we express our sincere appreciation for your recent donation. Your generous support helps us continue our ministries and serve our community.\n\nThank you for your faithfulness."
        ]);
    }
}

