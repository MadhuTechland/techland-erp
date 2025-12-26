<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\GithubApiService;

$service = app(GithubApiService::class);

echo "=== GitHub API Test ===\n\n";

// Test connection
echo "1. Testing connection...\n";
$result = $service->testConnection();
print_r($result);

echo "\n2. Configured repositories:\n";
$repos = $service->getConfiguredRepositories();
print_r($repos);

echo "\n3. Testing first repository fetch...\n";
if (!empty($repos[0])) {
    $repo = $repos[0];
    echo "Trying to fetch from: {$repo}\n";

    // Check if it has owner prefix
    if (strpos($repo, '/') === false) {
        echo "WARNING: Repository '{$repo}' is missing owner prefix!\n";
        echo "Format should be: owner/repo (e.g., techland/{$repo})\n";
    }
}

echo "\n=== Done ===\n";
