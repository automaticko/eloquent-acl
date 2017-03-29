<?php

namespace Automaticko\ACL\Console\Commands;

use Cocur\Slugify\Slugify;
use Illuminate\Console\Command;
use Illuminate\Database\Connection as DB;
use Illuminate\Config\Repository as Config;
use Illuminate\Database\Eloquent\Collection;

Trait RequirementsTrait
{
    protected function failsRequirements($command)
    {
        if (!$this->option('force') && !$this->config->get('acl.' . $command. '.enabled')) {
            $this->info(trans('acl::acl.' . $command . '.disabled'));

            return 1;
        }

        if (!class_exists($this->config->get('acl.models.role'))) {
            $this->info(trans('acl::acl.errors.no_role_model'));

            return 2;
        }

        if (!class_exists($this->config->get('acl.models.permission'))) {
            $this->info(trans('acl::acl.errors.no_permission_model'));

            return 3;
        }

        if (!$this->schema->hasTable('roles')) {
            $this->info(trans('acl::acl.errors.no_roles_table'));

            return 4;
        }

        if (!$this->schema->hasTable('permissions')) {
            $this->info(trans('acl::acl.errors.no_permissions_table'));

            return 5;
        }

        if (!$this->schema->hasTable('permission_role')) {
            $this->info(trans('acl::acl.errors.no_permission_role_table'));

            return 6;
        }

        if (!$this->schema->hasTable('permission_user')) {
            $this->info(trans('acl::acl.errors.no_permission_user_table'));

            return 7;
        }

        return false;
    }
}
