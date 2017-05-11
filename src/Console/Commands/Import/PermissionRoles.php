<?php

namespace Automaticko\ACL\Console\Commands\Import;

use Illuminate\Support\Collection;
use Illuminate\Database\Connection as DB;

class PermissionRoles
{
    /**
     * @var DB \Illuminate\Database\Connection
     */
    protected $db;

    protected $inserted;

    protected $deleted;

    protected $ignored;

    protected $errors;

    public function __construct(DB $db)
    {
        $this->db     = $db;
        $this->schema = $db->getSchemaBuilder();
    }

    public function getInserted()
    {
        return $this->inserted;
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function getDeleted()
    {
        return $this->deleted;
    }

    public function getIgnored()
    {
        return $this->ignored;
    }

    public function set(array $raw_roles, Collection $roles, Collection $permissions)
    {
        $permission_roles_names = $this->groupByRole($this->getPermissionRoles());

        $ignore_names = [];
        $insert_names = [];
        $delete_names = [];
        $error_names  = [];

        foreach ($raw_roles as $role_name => $raw_role) {
            $permission_names     = [];
            $raw_permission_names = !empty($raw_role['permissions']) ? $raw_role['permissions'] : [];

            // Sanitize permissions
            $errors = [];
            foreach ($raw_permission_names as $index => $permission_name) {
                if ($permissions->has($permission_name)) {
                    $permission_names[] = $permission_name;
                } else {
                    $errors[] = $permission_name;
                }
            }

            $ignore = [];
            $delete = [];

            if (!empty($permission_roles_names[$role_name])) {

                $permission_role_names = $permission_roles_names[$role_name];

                $insert = array_diff($permission_names, $permission_role_names);
                $delete = array_diff($permission_role_names, $permission_names);
                $ignore = array_intersect($permission_role_names, $permission_names);
            } else {
                $insert = $permission_names;
            }

            $insert_names[$role_name] = $insert;
            $delete_names[$role_name] = $delete;
            $ignore_names[$role_name] = $ignore;
            $error_names[$role_name]  = $errors;
        }

        $this->insert($insert_names, $roles, $permissions);
        $this->delete($delete_names, $roles, $permissions);

        $this->inserted = $insert_names;
        $this->deleted  = $delete_names;
        $this->ignored  = $ignore_names;
        $this->errors   = $error_names;

        return new Collection(array_merge($insert_names, $ignore_names));
    }

    protected function getPermissionRoles()
    {
        $query = $this->db->table('permission_role');
        $query->select(['permission_role.*', 'roles.name AS role_name', 'permissions.name AS permission_name']);
        $query->join('roles', 'role_id', '=', 'roles.id');
        $query->join('permissions', 'permission_id', '=', 'permissions.id');

        return $query->get();
    }

    protected function groupByRole(Collection $permission_roles)
    {
        $permission_roles_names = [];

        foreach ($permission_roles as $permission_role) {
            $permission_roles_names[$permission_role->role_name][] = $permission_role->permission_name;
        }

        return $permission_roles_names;
    }

    protected function insert(array $insert_names, Collection $roles, Collection $permissions)
    {
        $now = date('Y-m-d H:i:s');

        foreach ($insert_names as $role_name => $permission_names) {
            $role_id = $roles[$role_name]->id;
            foreach ($permission_names as $permission_name) {
                $permission_id = $permissions[$permission_name]->id;
                $this->db->table('permission_role')->insert([
                    'role_id'       => $role_id,
                    'permission_id' => $permission_id,
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ]);
            }
        }
    }

    protected function delete(array $delete_names, Collection $roles, Collection $permissions)
    {
        foreach ($delete_names as $role_name => $permission_names) {
            $role_id = $roles[$role_name]->id;
            foreach ($permission_names as $permission_name) {
                $permission_id = $permissions[$permission_name]->id;

                $this->db->table('permission_role')
                    ->where('role_id', $role_id)
                    ->where('permission_id', $permission_id)
                    ->delete();
            }
        }
    }
}
