<?php

namespace Automaticko\ACL\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Connection as DB;
use Illuminate\Config\Repository as Config;
use Illuminate\Foundation\Application as App;
use Automaticko\ACL\Console\Commands\Import\Roles;
use Automaticko\ACL\Console\Commands\Import\Permissions;
use Automaticko\ACL\Console\Commands\Import\PermissionRoles;
use Illuminate\Support\Collection;

class Import extends Command
{
    use RequirementsTrait;
    const COMMAND = 'import';

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
     * Create a new command instance.
     *
     * @param Config $config
     */
    public function __construct(Config $config, DB $db)
    {
        parent::__construct();
        $this->config = $config;
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

        $permissions = $this->handlePermissions();
        $roles = $this->handleRoles();
        $this->handlePermissionRoles($roles, $permissions);

        return 0;
    }

    protected function handlePermissions()
    {
        $this->permissionClass = $this->config->get('acl.models.permission');
        $raw_permissions       = $this->config->get('acl.import.permissions', []);
        $raw_permissions       = is_array($raw_permissions) ? $raw_permissions : [];

        $permissions_handler = new Permissions();
        $permissions         = $permissions_handler->set($raw_permissions, $this->permissionClass);
        $this->reportPermissions($permissions_handler->getInserted(),
            $permissions_handler->getUpdated(),
            $permissions_handler->getDeleted(),
            $permissions_handler->getIgnored());

        return $permissions;
    }

    protected function handleRoles()
    {
        $this->roleClass = $this->config->get('acl.models.role');
        $raw_roles       = $this->config->get('acl.import.roles', []);
        $raw_roles       = is_array($raw_roles) ? $raw_roles : [];

        $roles_handler = new Roles();
        $roles         = $roles_handler->set($raw_roles, $this->roleClass);
        $this->reportRoles($roles_handler->getInserted(),
            $roles_handler->getUpdated(),
            $roles_handler->getDeleted(),
            $roles_handler->getIgnored());

        return $roles;
    }

    protected function handlePermissionRoles(Collection $roles, Collection $permissions)
    {
        $raw_roles = $this->config->get('acl.import.roles', []);
        $raw_roles = is_array($raw_roles) ? $raw_roles : [];

        $permission_roles_handler = App::getInstance()->make(PermissionRoles::class);
        $permission_roles_handler->set($raw_roles, $roles, $permissions);
        $this->reportPermissionRoles($permission_roles_handler->getInserted(),
            $permission_roles_handler->getDeleted(),
            $permission_roles_handler->getIgnored(),
            $permission_roles_handler->getErrors());
    }

    protected function reportPermissions($inserted, $updated, $deleted, $ignored)
    {
        foreach ($inserted as $permission) {
            $this->info(trans('acl::acl.import.permission.insert',
                ['permission_name' => $permission->name, 'permission_id' => $permission->id]));
        }

        foreach ($updated as $permission) {
            $this->info(trans('acl::acl.import.permission.update',
                ['permission_name' => $permission->name, 'permission_id' => $permission->id]));
        }

        foreach ($deleted as $permission) {
            $this->info(trans('acl::acl.import.permission.delete',
                ['permission_name' => $permission->name, 'permission_id' => $permission->id]));
        }

        if ($this->option('v')) {
            foreach ($ignored as $permission) {
                $this->info(trans('acl::acl.import.permission.ignore',
                    ['permission_name' => $permission->name, 'permission_id' => $permission->id]));
            }
        }
    }

    protected function reportRoles($inserted, $updated, $deleted, $ignored)
    {
        foreach ($inserted as $role) {
            $this->info(trans('acl::acl.import.role.insert',
                ['role_name' => $role->name, 'role_id' => $role->id]));
        }

        foreach ($updated as $role) {
            $this->info(trans('acl::acl.import.role.update',
                ['role_name' => $role->name, 'role_id' => $role->id]));
        }

        foreach ($deleted as $role) {
            $this->info(trans('acl::acl.import.role.delete',
                ['role_name' => $role->name, 'role_id' => $role->id]));
        }

        if ($this->option('v')) {
            foreach ($ignored as $role) {
                $this->info(trans('acl::acl.import.role.ignore',
                    ['role_name' => $role->name, 'role_id' => $role->id]));
            }
        }
    }

    protected function reportPermissionRoles($inserted, $deleted, $ignored, $errors)
    {
        foreach ($inserted as $role_name => $permission_names) {
            foreach ($permission_names as $permission_name) {
                $this->info(trans('acl::acl.import.permission_role.insert',
                    ['role_name' => $role_name, 'permission_name' => $permission_name]));
            }
        }

        foreach ($deleted as $role_name => $permission_names) {
            foreach ($permission_names as $permission_name) {
                $this->error(trans('acl::acl.import.permission_role.delete',
                    ['role_name' => $role_name, 'permission_name' => $permission_name]));
            }
        }

        if ($this->option('v')) {
            foreach ($ignored as $role_name => $permission_names) {
                foreach ($permission_names as $permission_name) {
                    $this->line(trans('acl::acl.import.permission_role.ignore',
                        ['role_name' => $role_name, 'permission_name' => $permission_name]));
                }
            }
        }

        foreach ($errors as $role_name => $permission_names) {
            foreach ($permission_names as $permission_name) {
                $this->error(trans('acl::acl.import.permission_role.error',
                    ['role_name' => $role_name, 'permission_name' => $permission_name]));
            }
        }
    }
}
