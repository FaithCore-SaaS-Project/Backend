<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\SupportArticle;
use Illuminate\Support\Str;

$articles = [
    "Getting Started with FaithCore",
    "How to Add a New Member",
    "Managing Donations and Payments",
    "Setting Up Email Notifications",
    "Running Reports"
];

foreach ($articles as $index => $title) {
    SupportArticle::firstOrCreate(
        ['slug' => Str::slug($title)],
        [
            'title' => $title,
            'content' => 'This is the content for ' . $title,
            'views' => 100 - ($index * 10), // So they are ordered
            'status' => 'published'
        ]
    );
}

echo "Seeded Support Articles.\n";
