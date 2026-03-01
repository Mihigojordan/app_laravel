<?php

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;

class ApprovalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Create Roles
        $roles = [
            [
                'name' => 'HR',
                'label' => 'Human Resources',
                'description' => 'Responsible for HR approvals',
            ],
            [
                'name' => 'Finance_Head',
                'label' => 'Head of Finance',
                'description' => 'Responsible for Finance approvals',
            ],
        ];

        foreach ($roles as $roleData) {
            Role::firstOrCreate(['name' => $roleData['name']], $roleData);
        }

        // Create Permissions
        $permissions = [
            [
                'name' => 'approve_payroll',
                'label' => 'Approve Payroll',
                'description' => 'Permission to approve or reject payroll requests',
            ],
            [
                'name' => 'request_approval',
                'label' => 'Request Approval',
                'description' => 'Permission to submit processes for approval',
            ],
        ];

        foreach ($permissions as $permissionData) {
            Permission::firstOrCreate(['name' => $permissionData['name']], $permissionData);
        }

        // Assign Permissions to Roles
        $hrRole = Role::where('name', 'HR')->first();
        $financeHeadRole = Role::where('name', 'Finance_Head')->first();
        $ownerRole = Role::where('name', 'Owner')->first();

        $approvePayroll = Permission::where('name', 'approve_payroll')->first();
        $requestApproval = Permission::where('name', 'request_approval')->first();

        if ($hrRole && $approvePayroll) {
            $hrRole->permissions()->syncWithoutDetaching([$approvePayroll->id]);
        }
        if ($financeHeadRole && $approvePayroll) {
            $financeHeadRole->permissions()->syncWithoutDetaching([$approvePayroll->id]);
        }
        if ($ownerRole) {
            // Owner gets everything
            $allPerms = Permission::whereIn('name', ['approve_payroll', 'request_approval'])->pluck('id')->toArray();
            $ownerRole->permissions()->syncWithoutDetaching($allPerms);
        }
    }
}
