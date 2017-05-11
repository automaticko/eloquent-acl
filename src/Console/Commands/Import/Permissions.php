<?php

namespace Automaticko\ACL\Console\Commands\Import;

use Illuminate\Database\Eloquent\Collection;

class Permissions
{
    protected $class;

    protected $rawPermissions;

    protected $inserted;

    protected $updated;

    protected $deleted;

    protected $ignored;

    function __construct()
    {
        $this->inserted = new Collection();
        $this->updated  = new Collection();
        $this->deleted  = new Collection();
        $this->ignored  = new Collection();
    }

    public function getInserted()
    {
        return $this->inserted;
    }

    public function getUpdated()
    {
        return $this->updated;
    }

    public function getDeleted()
    {
        return $this->deleted;
    }

    public function getIgnored()
    {
        return $this->ignored;
    }

    public function set(array $raw_permissions, $class)
    {
        $this->class = $class;
        $this->rawPermissions = $raw_permissions;

        $raw_names = array_keys($raw_permissions);

        $permissions      = $class::all()->keyBy('name');
        $permission_names = $permissions->keys()->toArray();

        $update_names = array_intersect($raw_names, $permission_names);
        $insert_names = array_diff($raw_names, $permission_names);
        $delete_names = array_diff($permission_names, $raw_names);

        $this->inserted = $this->insert($insert_names);

        $this->updated = $this->update($update_names, $permissions);

        $this->deleted = $this->delete($delete_names, $permissions);

        $this->ignored = $this->ignore($permissions, $this->updated, $this->deleted);

        return new Collection(array_merge($this->inserted->toArray(), $this->updated->toArray(), $this->ignored->toArray()));
    }

    protected function insert(array $insert_names)
    {
        $inserted = new Collection();

        foreach ($insert_names as $permission_name) {
            $raw_permission = $this->rawPermissions[$permission_name];
            $permission     = new $this->class();
            $permission->setRawAttributes([
                'name'        => $permission_name,
                'slug'        => !empty($raw_permission['slug']) ? $raw_permission['slug'] : str_slug($permission_name),
                'description' => !empty($raw_permission['description']) ? $raw_permission['description'] : null,
                'model'       => !empty($raw_permission['model']) ? $raw_permission['model'] : null,
            ]);
            $permission->save();

            $inserted->put($permission_name, $permission);
        }

        return $inserted;
    }

    protected function update(array $update_names, Collection $permissions)
    {
        $updated = new Collection();

        foreach ($update_names as $permission_name) {
            $raw_permission = $this->rawPermissions[$permission_name];
            $permission     = $permissions[$permission_name];

            $slug        = !empty($raw_permission['slug']) ? $raw_permission['slug'] : $permission->slug;
            $description = !empty($raw_permission['description']) ? $raw_permission['description'] : null;
            $model       = !empty($raw_permission['model']) ? $raw_permission['model'] : null;

            $permission->slug        = $slug;
            $permission->description = $description;
            $permission->model       = $model;
            if ($permission->isDirty()) {
                $permission->save();

                $updated->put($permission_name, $permission);
            }
        }

        return $updated;
    }

    protected function delete(array $delete_names, Collection $permissions)
    {
        $deleted = new Collection();

        foreach ($delete_names as $permission_name) {
            $permission = $permissions[$permission_name];
            ($this->class)::where('name', 'like', $permission_name)->delete();
            $permissions->forget($permission_name);

            $deleted->put($permission_name, $permission);
        }

        return $deleted;
    }

    protected function ignore(Collection $permissions, Collection $updated, Collection $deleted)
    {
        $ignored = new Collection();

        $modified = new Collection(array_merge($updated->all(), $deleted->all()));

        foreach ($permissions as $permission) {
            if (!$modified->has($permission->name)) {
                $ignored->put($permission->name, $permission);
            }
        }

        return $ignored;
    }
}
