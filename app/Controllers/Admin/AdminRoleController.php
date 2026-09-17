<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\AdminRoleRepository;

class AdminRoleController extends BaseController
{
    private AdminRoleRepository $roleRepo;

    public function __construct()
    {
        $this->roleRepo = new AdminRoleRepository();
    }

    public function index(Request $request): Response
    {
        $roles = $this->roleRepo->getRoles();
        $permissions = $this->roleRepo->getPermissions();
        $staff = $this->roleRepo->getStaffMembers();

        $selectedRoleId = (int)$request->query('role_id', $roles[0]['id'] ?? 1);
        $rolePermissions = $this->roleRepo->getRolePermissions($selectedRoleId);

        return view('admin/roles/index', [
            'roles' => $roles,
            'permissions' => $permissions,
            'staff' => $staff,
            'staff_users' => $staff,
            'selected_role_id' => $selectedRoleId,
            'role_permissions' => $rolePermissions,
        ], 'admin');
    }

    public function updatePermissions(Request $request, string $id): Response
    {
        $roleId = (int)$id;
        $permissionIds = $request->input('permissions', []);
        if (!is_array($permissionIds)) {
            $permissionIds = [];
        }

        $this->roleRepo->saveRolePermissions($roleId, $permissionIds);
        flash('success', "Permissions updated for role #{$roleId}.");
        return $this->redirect("/admin/roles?role_id={$roleId}");
    }

    public function assignStaffRole(Request $request): Response
    {
        $adminId = (int)$request->input('admin_id');
        $roleId = (int)$request->input('role_id');

        $this->roleRepo->updateStaffRole($adminId, $roleId);
        flash('success', "Staff member role updated.");
        return $this->redirect('/admin/roles');
    }
}
