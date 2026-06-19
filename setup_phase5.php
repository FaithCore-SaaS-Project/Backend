<?php

$modelsDir = __DIR__ . '/app/Models';

$updates = [
    'FinanceIncome.php' => "    public function category() { return \$this->belongsTo(FinanceCategory::class, 'category_id'); }",
    'FinanceExpense.php' => "    public function category() { return \$this->belongsTo(FinanceCategory::class, 'category_id'); }",
    'Budget.php' => "    public function category() { return \$this->belongsTo(FinanceCategory::class, 'category_id'); }",
    'Document.php' => "    public function category() { return \$this->belongsTo(DocumentCategory::class, 'category_id'); }\n    public function uploader() { return \$this->belongsTo(User::class, 'uploaded_by'); }",
    'Certificate.php' => "    public function member() { return \$this->belongsTo(Member::class); }",
    'Subscription.php' => "    public function plan() { return \$this->belongsTo(Plan::class); }",
    'AuditLog.php' => "    public function user() { return \$this->belongsTo(User::class); }"
];

foreach ($updates as $file => $content) {
    $path = $modelsDir . '/' . $file;
    if (file_exists($path)) {
        $fileContent = file_get_contents($path);
        // check if method already exists using a rough heuristic
        $checkMethod = explode('(', explode('public function ', $content)[1] ?? '')[0] ?? 'random_non_existent_method_name';
        if (strpos($fileContent, "function $checkMethod") === false) {
            $fileContent = preg_replace('/}\s*$/', "\n$content\n}", $fileContent);
            file_put_contents($path, $fileContent);
            echo "Updated $file\n";
        }
    } else {
        echo "File $file not found.\n";
    }
}

// Generate Controllers
$controllers = [
    'DashboardController',
    'DocumentController',
    'CertificateController',
    'LetterController',
    'UserController',
    'RoleController',
    'PermissionController',
    'SubscriptionController',
    'SettingsController',
    'NotificationController'
];

foreach ($controllers as $controller) {
    if (!file_exists(__DIR__ . "/app/Http/Controllers/Api/{$controller}.php")) {
        echo shell_exec("php artisan make:controller Api/{$controller} --api");
    } else {
        echo "Controller $controller already exists.\n";
    }
}

echo "\nDone!\n";
