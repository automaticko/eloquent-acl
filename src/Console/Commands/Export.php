<?php

namespace Automaticko\ACL\Console\Commands;

use ReflectionClass;
use Illuminate\Console\Command;
use Illuminate\Database\Connection as DB;
use Illuminate\Config\Repository as Config;
use Automaticko\ACL\Services\ConfigExportService;

class Export extends Command
{
    use RequirementsTrait;
    const COMMAND = 'export';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'acl:export {--v} {--force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Exports ACL permissions and roles to acl config file';

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
     * @var \Illuminate\Database\Connection
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
     * @param ConfigExportService $config_service
     *
     * @return mixed
     */
    public function handle(ConfigExportService $config_service)
    {
        $this->roleClass       = $this->config->get('acl.models.role');
        $this->permissionClass = $this->config->get('acl.models.permission');

        if ($error_level = $this->failsRequirements(self::COMMAND)) {
            return $error_level;
        }

        $acl                      = $this->config->get('acl');
        $acl['enabled']           = "![CDATA[env('ACL_ENABLED'," . ($acl['enabled'] ? 'true' : 'false') . ")]]";
        $acl['export']['enabled'] = "![CDATA[env('ACL_EXPORT_ENABLED'," . ($acl['export']['enabled'] ? 'true' : 'false') . ")]]";
        $acl['import']['enabled'] = "![CDATA[env('ACL_IMPORT_ENABLED'," . ($acl['import']['enabled'] ? 'true' : 'false') . ")]]";

        $permissions     = $this->db->table('permissions')->get();
        $raw_permissions = [];
        foreach ($permissions as $permission) {
            $raw_permission = [
                'slug'        => $permission->slug,
                'description' => $permission->description,
                'model'       => $permission->model,
            ];

            $raw_permissions[$permission->slug] = $raw_permission;
        }
        $acl['import']['permissions'] = $raw_permissions;

        $roles     = $this->db->table('roles')->get();
        $raw_roles = [];
        foreach ($roles as $role) {
            $raw_role = [
                'slug'        => $role->slug,
                'description' => $role->description,
                'level'       => $role->level,
            ];

            $raw_roles[$role->slug] = $raw_role;
        }

        $query = $this->db->table('permission_role');
        $query->select([
            'roles.slug AS role_name',
            'permissions.slug AS permission_name',
        ]);
        $query->join('roles', 'role_id', '=', 'roles.id');
        $query->join('permissions', 'permission_id', '=', 'permissions.id');
        $query->orderBy('permission_role.role_id');

        $reflection_roles = new ReflectionClass($this->roleClass);
        $role_constants   = array_flip($this->sanitizeConstants($reflection_roles->getConstants()));

        foreach ($query->get() as $permission_role) {
            $role_name       = $permission_role->role_name;
            $permission_name = $permission_role->permission_name;

            if (!empty($role_constants[$role_name])) {
                $role_name = "\\{$this->roleClass}::{$role_constants[$role_name]}";
            }

            $raw_roles[$role_name]['permissions'][] = $permission_name;
        }
        $acl['import']['roles'] = $raw_roles;

        $bytes = $config_service->export($acl, 'acl');

        $this->info(trans('acl::acl.export.success', ['bytes' => $bytes]));

        return 0;
    }

    protected function sanitizeConstants($constants)
    {
        foreach ($constants as $name => $value) {
            if (!is_numeric($value) && !is_string($value)) {
                unset($constants[$name]);
            }
        }

        return $constants;
    }
}
