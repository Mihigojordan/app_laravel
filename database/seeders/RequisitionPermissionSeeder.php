<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Permission;
use App\Models\Role;

class RequisitionPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $permissions = [
            'requisitions_view',
            'requisitions_add',
            'requisitions_edit',
            'requisitions_delete',
        ];

        foreach ($permissions as $permissionName) {
            // Create permission if it doesn't exist
            $permission = Permission::firstOrCreate(['name' => $permissionName]);

            // Assign to Admin role (assuming Admin role exists and has id 1 or name 'Admin')
            $adminRole = Role::where('name', 'admin')->first();
            if ($adminRole) {
                if (!$adminRole->permissions->contains($permission->id)) {
                    $adminRole->permissions()->attach($permission->id);
                }
            }
        }
        
        // Also ensure Purchases permissions are set if they are missing, as Sidebar uses them
        $purchasesPermissions = [
            'Purchases_view',
            'Purchases_add',
            'Purchases_edit',
            'Purchases_delete',
        ];
        
        foreach ($purchasesPermissions as $permissionName) {
            $permission = Permission::firstOrCreate(['name' => $permissionName]);
            $adminRole = Role::where('name', 'admin')->first();
            if ($adminRole) {
                if (!$adminRole->permissions->contains($permission->id)) {
                    $adminRole->permissions()->attach($permission->id);
                }
            }
        }
    }
}
