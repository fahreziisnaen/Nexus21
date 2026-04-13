<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$user = \App\Models\User::where('username', 'joky')->first();
\Illuminate\Support\Facades\Auth::login($user);

$request = \Illuminate\Http\Request::create('/dashboard', 'GET');
$response = $kernel->handle($request);

$content = $response->getContent();

if (strpos($content, 'Belum ada data kelas dengan pelanggaran bulan ini') !== false) {
    echo "NO DATA RENDERED!\n";
} else {
    echo "DATA IS RENDERED!\n";
    // Check if X-3 is inside
    if (strpos($content, '>X-3<') !== false) {
        echo "X-3 IS IN THE HTML!\n";
    }
}
echo "Done.\n";
