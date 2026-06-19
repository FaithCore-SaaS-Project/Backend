<?php

$dir = __DIR__ . '/app/Models/';
$models = [
    'AuditLog.php',
    'Backup.php',
    'Certificate.php',
    'Department.php',
    'Document.php',
    'Event.php',
    'Family.php',
    'FinanceExpense.php',
    'FinanceIncome.php',
    'Letter.php',
    'Member.php',
    'Notification.php',
    'Subscription.php'
];

foreach ($models as $model) {
    $path = $dir . $model;
    if (file_exists($path)) {
        $content = file_get_contents($path);

        // Don't add trait if it's already there
        if (strpos($content, 'BelongsToChurch') !== false) {
            continue;
        }

        // Add use App\Models\Traits\BelongsToChurch; after namespace App\Models;
        $content = preg_replace('/namespace App\\\\Models;/', "namespace App\\Models;\n\nuse App\\Models\\Traits\\BelongsToChurch;", $content);

        // Add use BelongsToChurch; inside the class
        $content = preg_replace('/class [a-zA-Z0-9_]+ extends Model\s*\{/', "$0\n    use BelongsToChurch;\n", $content);

        file_put_contents($path, $content);
        echo "Updated $model\n";
    }
}
