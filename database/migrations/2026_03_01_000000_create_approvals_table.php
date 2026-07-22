<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
            $table->integer('user_id'); // requested_by, matches users.id (signed int)
            $table->integer('current_level')->default(1);
            $table->timestamps();

            $table->index(['approvable_id', 'approvable_type']);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
        }

        // Seed Roles and Permissions. IDs are pinned well above the ranges
        // RoleSeeder/PermissionsSeeder hardcode (id 1 for "Owner", ids 1-147
        // for permissions), since migrations run before `--seed`'s seeders
        // and would otherwise grab those low auto-increment ids first on an
        // empty table. Uses the DB query builder rather than
        // Role::firstOrCreate()/Permission::firstOrCreate() because both
        // models have `id` in $guarded, so Eloquent mass-assignment silently
        // drops an explicit 'id' and falls back to auto-increment.
        $roles = [
            ['id' => 1001, 'name' => 'HR', 'label' => 'Human Resources', 'description' => 'Responsible for HR approvals'],
            ['id' => 1002, 'name' => 'Finance_Head', 'label' => 'Head of Finance', 'description' => 'Responsible for Finance approvals'],
        ];
        foreach ($roles as $role) {
            DB::table('roles')->updateOrInsert(['name' => $role['name']], $role);
        }

        $permissions = [
            ['id' => 1001, 'name' => 'approve_payroll', 'label' => 'Approve Payroll', 'description' => 'Permission to approve or reject payroll requests'],
            ['id' => 1002, 'name' => 'request_approval', 'label' => 'Request Approval', 'description' => 'Permission to submit processes for approval'],
        ];
        foreach ($permissions as $permission) {
            DB::table('permissions')->updateOrInsert(['name' => $permission['name']], $permission);
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
