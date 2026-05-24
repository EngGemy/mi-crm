<?php

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$allUsers = DB::table('users')->select('id', 'email', 'name', 'is_active', 'password', 'email_verified_at')->get();

if ($allUsers->isEmpty()) {
    echo "❌ No users at all in the database!\n";
    exit(1);
}

foreach ($allUsers as $user) {
    echo "User {$user->id}: {$user->email} | {$user->name} | active=".($user->is_active ? '1' : '0').' | verified='.($user->email_verified_at ?? 'NULL')."\n";
    echo '  Password prefix: '.substr($user->password, 0, 40)."...\n";

    $roles = DB::table('model_has_roles')
        ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
        ->where('model_id', $user->id)
        ->pluck('roles.name');
    echo '  Roles: '.implode(', ', $roles->toArray())."\n";
}
