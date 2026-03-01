<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateApprovalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('approvals')) {
            Schema::create('approvals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('approvable_id');
            $table->string('approvable_type');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->unsignedBigInteger('user_id'); // requested_by
            $table->integer('current_level')->default(1);
            $table->timestamps();

            $table->index(['approvable_id', 'approvable_type']);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
        }

        // Seed Roles and Permissions
        $roles = [
            ['name' => 'HR', 'label' => 'Human Resources', 'description' => 'Responsible for HR approvals'],
            ['name' => 'Finance_Head', 'label' => 'Head of Finance', 'description' => 'Responsible for Finance approvals'],
        ];
        foreach ($roles as $role) {
            \App\Models\Role::firstOrCreate(['name' => $role['name']], $role);
        }

        $permissions = [
            ['name' => 'approve_payroll', 'label' => 'Approve Payroll', 'description' => 'Permission to approve or reject payroll requests'],
            ['name' => 'request_approval', 'label' => 'Request Approval', 'description' => 'Permission to submit processes for approval'],
        ];
        foreach ($permissions as $permission) {
            \App\Models\Permission::firstOrCreate(['name' => $permission['name']], $permission);
        }

        // Assign perms to Owner
        $owner = \App\Models\Role::where('name', 'Owner')->first();
        if ($owner) {
            $p_ids = \App\Models\Permission::whereIn('name', ['approve_payroll', 'request_approval'])->pluck('id')->toArray();
            $owner->permissions()->syncWithoutDetaching($p_ids);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('approvals');
    }
}
