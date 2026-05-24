<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

app()[PermissionRegistrar::class]->forgetCachedPermissions();
Role::firstOrCreate(['name' => 'super_admin']);

$u = User::where('email', 'admin@mi-cnc.com')->first();
if (! $u) {
    $u = new User;
    $u->email = 'admin@mi-cnc.com';
    $u->name = 'Admin';
    $u->password = 'temp';
    $u->is_active = true;
    $u->save();
}

// ???? ????? ?????? ?????? ?? cast (hashed) ???? ??????? ???????
DB::table('users')->where('email', 'admin@mi-cnc.com')->update([
    'password' => Hash::make('password'),
    'is_active' => 1,
]);

$u = $u->fresh();
$u->syncRoles(['super_admin']);

echo PHP_EOL.'email   = '.$u->email.PHP_EOL;
echo 'active  = '.($u->is_active ? 1 : 0).PHP_EOL;
echo 'roles   = '.$u->getRoleNames()->implode(',').PHP_EOL;
echo "MATCH 'password' = ".(Hash::check('password', $u->password) ? 'YES' : 'NO').PHP_EOL;
echo 'users in DB = '.User::count().PHP_EOL;
