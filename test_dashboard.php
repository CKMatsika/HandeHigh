<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $school = App\Models\School::firstOrCreate(['code' => 'HANDE'], ['name' => 'Hande', 'currency' => 'USD']);
    $user = App\Models\User::firstOrCreate(['email' => 'superadmin@handehigh.test'], ['name' => 'Super', 'password' => bcrypt('password'), 'school_id' => $school->id]);
    $user->assignRole('super-admin');
    Auth::login($user);
    $response = app(App\Http\Controllers\DashboardController::class)->index();
    echo 'Success';
} catch (\Exception $e) {
    echo get_class($e) . ': ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine();
}
