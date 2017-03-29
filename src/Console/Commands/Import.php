<?php

namespace Automaticko\ACL\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Connection as DB;
use Illuminate\Config\Repository as Config;
use Illuminate\Database\Eloquent\Collection;

class Import extends Command
{
    use RequirementsTrait;
    const DEFAULT_ROLE_LEVEL = 1;
    const COMMAND            = 'import';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'acl:import {--force} {--v}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sets ACL permissions';

    protected $roleClass;

    protected $permissionClass;

    /**
     * @var Config \Illuminate\Config\Repository
     */
    protected $config;

    /**
     * @var \Illuminate\Database\Schema\Builder
     */
    protected $schema;

    /**
     * @var DB \Illuminate\Database\Connection
     */
    protected $db;

    /**
     * Create a new command instance.
     *
     * @param Config $config
     * @param DB     $db
     */
    public function __construct(Config $config, DB $db)
    {
        parent::__construct();
        $this->config = $config;
        $this->db     = $db;
        $this->schema = $db->getSchemaBuilder();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if ($error_level = $this->failsRequirements(self::COMMAND)) {
            return $error_level;
        }

        $this->roleClass       = $this->config->get('acl.models.role');
        $this->permissionClass = $this->config->get('acl.models.permission');

        $raw_permissions = $this->config->get('acl.import.permissions', []);
        $raw_roles       = $this->config->get('acl.import.roles', []);

        $raw_permissions = is_array($raw_permissions) ? $raw_permissions : [];
        $raw_roles       = is_array($raw_roles) ? $raw_roles : [];

        $permissions = $this->setPermissions($raw_permissions);
        $roles       = $this->setRoles($raw_roles);

        $this->setPermissionRoles($raw_roles, $permissions, $roles);

        return 0;
    }

    protected function setPermissions(array $raw_permissions)
    {
        $permission_class     = $this->permissionClass;
        $raw_permission_slugs = array_keys($raw_permissions);

        $db_permissions      = $permission_class::all()->keyBy('slug');
        $db_permission_slugs = $db_permissions->keys()->toArray();

        $update_permission_slugs = array_intersect($raw_permission_slugs, $db_permission_slugs);
        $insert_permission_slugs = array_diff($raw_permission_slugs, $db_permission_slugs);
        $delete_permission_slugs = array_diff($db_permission_slugs, $raw_permission_slugs);
        $ignore_permission_slugs = [];

        foreach ($insert_permission_slugs as $permission_slug) {
            $raw_permission = $raw_permissions[$permission_slug];
            $permission     = new $permission_class();
            $permission->setRawAttributes([
                'slug'        => $permission_slug,
                'name'        => !empty($raw_permission['name']) ? $raw_permission['name'] : $permission_slug,
                'description' => !empty($raw_permission['description']) ? $raw_permission['description'] : null,
                'model'       => !empty($raw_permission['model']) ? $raw_permission['model'] : null,
            ]);
            $permission->save();

            $permission_id = $permission->id;
            $this->info(trans('acl::acl.import.permission.insert',
                ['permission_slug' => $permission_slug, 'permission_id' => $permission_id]));

            $db_permissions->put($permission_slug, $permission);
        }

        foreach ($update_permission_slugs as $permission_slug) {
            $raw_permission = $raw_permissions[$permission_slug];
            $db_permission  = $db_permissions[$permission_slug];
            $permission_id  = $db_permission->id;

            $name        = !empty($raw_permission['name']) ? $raw_permission['name'] : $permission_slug;
            $description = !empty($raw_permission['description']) ? $raw_permission['description'] : null;
            $model       = !empty($raw_permission['model']) ? $raw_permission['model'] : null;

            $db_permission->name        = $name;
            $db_permission->description = $description;
            $db_permission->model       = $model;
            if ($db_permission->isDirty()) {
                $db_permission->save();
                $this->info(trans('acl::acl.import.permission.update',
                    ['permission_name' => $permission_slug, 'permission_id' => $permission_id]));
            } else {
                $ignore_permission_slugs[] = $permission_slug;
            }
        }

        foreach ($delete_permission_slugs as $permission_slug) {
            $permission_id = $db_permissions[$permission_slug]->id;
            $permission_class::where('name', 'like', $permission_slug)->delete();
            $db_permissions->forget($permission_slug);
            $this->error(trans('acl::acl.import.permission.delete',
                ['permission_slug' => $permission_slug, 'permission_id' => $permission_id]));
        }

        if ($this->option('v')) {
            foreach ($ignore_permission_slugs as $permission_slug) {
                $permission_id = $db_permissions[$permission_slug]->id;
                $this->line(trans('acl::acl.import.permission.ignore',
                    ['permission_slug' => $permission_slug, 'permission_id' => $permission_id]));
            }
        }

        return $db_permissions;
    }

    protected function setRoles(array $raw_roles)
    {
        $role_class     = $this->roleClass;
        $raw_role_slugs = array_keys($raw_roles);

        $db_roles = $role_class::all()->keyBy('slug');

        $db_role_slugs = $db_roles->keys()->toArray();

        $update_role_slugs = array_intersect($raw_role_slugs, $db_role_slugs);
        $insert_role_slugs = array_diff($raw_role_slugs, $db_role_slugs);
        $delete_role_slugs = array_diff($db_role_slugs, $raw_role_slugs);
        $ignore_role_slugs = [];

        foreach ($insert_role_slugs as $role_slug) {
            $raw_role = $raw_roles[$role_slug];

            $role = new $role_class();
            $role->setRawAttributes([
                'slug'        => $role_slug,
                'name'        => !empty($raw_role['name']) ? $raw_role['name'] : $role_slug,
                'description' => !empty($raw_role['description']) ? $raw_role['description'] : null,
                'level'       => isset($raw_role['level']) ? $raw_role['level'] : self::DEFAULT_ROLE_LEVEL,
            ]);
            $role->save();
            $role_id = $role->id;
            $this->info(trans('acl::acl.import.role.insert', ['role_slug' => $role_slug, 'role_id' => $role_id]));

            $db_roles->put($role_slug, $role);
        }

        foreach ($update_role_slugs as $role_slug) {
            $raw_role = $raw_roles[$role_slug];
            $db_role  = $db_roles[$role_slug];
            $role_id  = $db_role->id;

            $name        = !empty($raw_role['name']) ? $raw_role['name'] : $role_slug;
            $description = !empty($raw_role['description']) ? $raw_role['description'] : null;
            $level       = !empty($raw_role['level']) ? $raw_role['level'] : null;

            $db_role->name        = $name;
            $db_role->description = $description;
            $db_role->level       = $level;
            if ($db_role->isDirty()) {
                $db_role->save();
                $this->info(trans('acl::acl.import.role.update',
                    ['role_slug' => $role_slug, 'role_id' => $role_id]));
            } else {
                $ignore_role_slugs[] = $role_slug;
            }
        }

        foreach ($delete_role_slugs as $role_slug) {
            $role_class::where('slug', 'like', $role_slug)->delete();
            $this->error(trans('acl::acl.import.role.delete',
                ['role_slug' => $role_slug, 'role_id' => $db_roles[$role_slug]]));
        }

        if ($this->option('v')) {
            foreach ($ignore_role_slugs as $role_slug) {
                $role_id = $db_roles[$role_slug]->id;
                $this->line(trans('acl::acl.import.role.ignore',
                    ['role_slug' => $role_slug, 'role_id' => $role_id]));
            }
        }

        return $db_roles;
    }

    protected function setPermissionRoles(array $raw_roles, Collection $permissions, Collection $roles)
    {
        $query = $this->db->table('permission_role');
        $query->select(['permission_role.*', 'roles.slug AS role_slug', 'permissions.slug AS permission_slug']);
        $query->join('roles', 'role_id', '=', 'roles.id');
        $query->join('permissions', 'permission_id', '=', 'permissions.id');

        $db_permission_roles       = $query->get();
        $db_permission_roles_slugs = [];

        // Group permissions by role
        foreach ($db_permission_roles as $db_permission_role) {
            $db_permission_roles_slugs[$db_permission_role->role_slug][] = $db_permission_role->permission_slug;
        }

        $ignore_permission_roles_slugs = [];
        $insert_permission_roles_slugs = [];
        $delete_permission_roles_slugs = [];
        $error_permission_slugs        = [];
        foreach ($raw_roles as $role_slug => $raw_role) {
            $permission_slugs     = [];
            $raw_permission_slugs = !empty($raw_role['permissions']) ? $raw_role['permissions'] : [];

            // Sanitize permissions
            $errors = [];
            foreach ($raw_permission_slugs as $index => $permission_slug) {
                if ($permissions->has($permission_slug)) {
                    $permission_slugs[] = $permission_slug;
                } else {
                    $errors[] = $permission_slug;
                }
            }

            $ignore = [];
            $delete = [];

            if (!empty($db_permission_roles_slugs[$role_slug])) {

                $permission_role_slugs = $db_permission_roles_slugs[$role_slug];

                $insert = array_diff($permission_slugs, $permission_role_slugs);
                $delete = array_diff($permission_role_slugs, $permission_slugs);
                $ignore = array_intersect($permission_role_slugs, $permission_slugs);
            } else {
                $insert = $permission_slugs;
            }

            $insert_permission_roles_slugs[$role_slug] = $insert;
            $delete_permission_roles_slugs[$role_slug] = $delete;
            $ignore_permission_roles_slugs[$role_slug] = $ignore;
            $error_permission_slugs[$role_slug]        = $errors;
        }

        $now = date('Y-m-d H:i:s');

        foreach ($insert_permission_roles_slugs as $role_slug => $permission_slugs) {
            $role_id = $roles[$role_slug]->id;
            foreach ($permission_slugs as $permission_slug) {
                $permission_id = $permissions[$permission_slug]->id;
                $this->db->table('permission_role')->insert([
                    'role_id'       => $role_id,
                    'permission_id' => $permission_id,
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ]);

                $this->info(trans('acl::acl.import.permission_role.insert',
                    ['role_slug' => $role_slug, 'permission_slug' => $permission_slug]));
            }
        }

        foreach ($delete_permission_roles_slugs as $role_slug => $permission_slugs) {
            $role_id = $roles[$role_slug]->id;
            foreach ($permission_slugs as $permission_slug) {
                $permission_id = $permissions[$permission_slug]->id;

                $this->db->table('permission_role')
                    ->where('role_id', $role_id)
                    ->where('permission_id', $permission_id)
                    ->delete();

                $this->error(trans('acl::acl.import.permission_role.delete',
                    ['role_slug' => $role_slug, 'permission_slug' => $permission_slug]));
            }
        }

        if ($this->option('v')) {
            foreach ($ignore_permission_roles_slugs as $role_slug => $permission_slugs) {
                $role_id = $roles[$role_slug]->id;
                foreach ($permission_slugs as $permission_slug) {
                    $permission_id = $permissions[$permission_slug]->id;
                    $this->line(trans('acl::acl.import.permission_role.ignore',
                        ['role_slug' => $role_slug, 'permission_slug' => $permission_slug]));
                }
            }
        }

        foreach ($error_permission_slugs as $role_slug => $permission_slugs) {
            foreach ($permission_slugs as $permission_slug) {
                $this->error(trans('acl::acl.import.permission_role.error',
                    [
                        'role_slug'       => $role_slug,
                        'permission_slug' => $permission_slug,
                    ]));
            }
        }
    }
}
