<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

use App\Models\Role;
use App\Models\Permission;
use Illuminate\Support\Facades\Artisan;

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Seeding Approvals Roles and Permissions...\n";

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
    echo "Role {$roleData['name']} checked/created.\n";
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
    echo "Permission {$permissionData['name']} checked/created.\n";
}

// Assign Permissions to Roles
$hrRole = Role::where('name', 'HR')->first();
$financeHeadRole = Role::where('name', 'Finance_Head')->first();
$ownerRole = Role::where('name', 'Owner')->first();

$approvePayroll = Permission::where('name', 'approve_payroll')->first();
$requestApproval = Permission::where('name', 'request_approval')->first();

if ($hrRole && $approvePayroll) {
    $hrRole->permissions()->syncWithoutDetaching([$approvePayroll->id]);
    echo "Assigned approve_payroll to HR.\n";
}
if ($financeHeadRole && $approvePayroll) {
    $financeHeadRole->permissions()->syncWithoutDetaching([$approvePayroll->id]);
    echo "Assigned approve_payroll to Finance_Head.\n";
}
if ($ownerRole) {
    $allPerms = Permission::whereIn('name', ['approve_payroll', 'request_approval'])->pluck('id')->toArray();
    $ownerRole->permissions()->syncWithoutDetaching($allPerms);
    echo "Assigned all perms to Owner.\n";
}

echo "Done!\n";
