<?php

use App\Models\Permission;
use App\Models\Role;

$permissions = [
    'requisitions_view',
    'requisitions_add',
    'requisitions_edit',
    'requisitions_delete',
];

foreach ($permissions as $permissionName) {
    $permission = Permission::firstOrCreate(['name' => $permissionName]);
    $adminRole = Role::where('name', 'Admin')->first() ?? Role::where('name', 'admin')->first();
    if ($adminRole) {
        if (!$adminRole->permissions->contains($permission->id)) {
            $adminRole->permissions()->attach($permission->id);
            echo "Assigned $permissionName to Admin.\n";
        }
    }
}

$purchasesPermissions = [
    'Purchases_view',
    'Purchases_add',
    'Purchases_edit',
    'Purchases_delete',
];

foreach ($purchasesPermissions as $permissionName) {
    $permission = Permission::firstOrCreate(['name' => $permissionName]);
    $adminRole = Role::where('name', 'Admin')->first() ?? Role::where('name', 'admin')->first();
    if ($adminRole) {
        if (!$adminRole->permissions->contains($permission->id)) {
            $adminRole->permissions()->attach($permission->id);
            echo "Assigned $permissionName to Admin.\n";
        }
    }
}
