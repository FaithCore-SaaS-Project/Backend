<?php

$churchModel = <<<EOD
    public function users() { return \$this->hasMany(User::class); }
    public function members() { return \$this->hasMany(Member::class); }
    public function departments() { return \$this->hasMany(Department::class); }
    public function events() { return \$this->hasMany(Event::class); }
EOD;

$memberModel = <<<EOD
    public function family() { return \$this->belongsTo(Family::class); }
    public function certificates() { return \$this->hasMany(Certificate::class); }
    public function departments() { return \$this->belongsToMany(Department::class, 'department_members'); }
    public function events() { return \$this->belongsToMany(Event::class, 'event_registrations'); }
EOD;

$familyModel = <<<EOD
    public function members() { return \$this->hasMany(Member::class); }
EOD;

$departmentModel = <<<EOD
    public function members() { return \$this->belongsToMany(Member::class, 'department_members'); }
    public function leader() { return \$this->belongsTo(Member::class, 'leader_id'); }
EOD;

$eventModel = <<<EOD
    public function members() { return \$this->belongsToMany(Member::class, 'event_registrations'); }
EOD;

$updates = [
    'Church.php' => $churchModel,
    'Member.php' => $memberModel,
    'Family.php' => $familyModel,
    'Department.php' => $departmentModel,
    'Event.php' => $eventModel,
];

foreach ($updates as $file => $content) {
    $path = __DIR__ . '/app/Models/' . $file;
    if (file_exists($path)) {
        $fileContent = file_get_contents($path);
        if (strpos($fileContent, 'function users()') === false && strpos($fileContent, 'function members()') === false) {
            $fileContent = preg_replace('/}\s*$/', "\n$content\n}", $fileContent);
            file_put_contents($path, $fileContent);
            echo "Updated $file\n";
        }
    }
}
