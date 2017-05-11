<?php

namespace Automaticko\ACL\Console\Commands\Import;

use Illuminate\Database\Eloquent\Collection;

class Roles
{
    const DEFAULT_ROLE_LEVEL = 1;

    protected $class;

    protected $rawRoles;

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

    public function set(array $raw_roles, $class)
    {
        $this->class = $class;
        $this->rawRoles = $raw_roles;

        $raw_names = array_keys($raw_roles);

        $roles = $class::all()->keyBy('name');

        $role_names = $roles->keys()->toArray();

        $update_names = array_intersect($raw_names, $role_names);
        $insert_names = array_diff($raw_names, $role_names);
        $delete_names = array_diff($role_names, $raw_names);

        $this->inserted = $this->insert($insert_names);

        $this->updated = $this->update($update_names, $roles);

        $this->deleted = $this->delete($delete_names, $roles);

        $this->ignored = $this->ignore($roles, $this->updated, $this->deleted);

        return new Collection(array_merge($this->inserted->all(), $this->updated->all(), $this->ignored->all()));
    }

    protected function insert(array $insert_names)
    {
        $inserted = new Collection();

        foreach ($insert_names as $role_name) {
            $raw_role = $this->rawRoles[$role_name];

            $role = new $this->class();
            $role->setRawAttributes([
                'name'        => $role_name,
                'slug'        => !empty($raw_role['slug']) ? $raw_role['slug'] : str_slug($role_name),
                'description' => !empty($raw_role['description']) ? $raw_role['description'] : null,
                'level'       => isset($raw_role['level']) ? $raw_role['level'] : self::DEFAULT_ROLE_LEVEL,
            ]);
            $role->save();

            $inserted->put($role_name, $role);
        }

        return $inserted;
    }

    protected function update(array $update_names, Collection $roles)
    {
        $updated = new Collection();

        foreach ($update_names as $role_name) {
            $raw_role = $this->rawRoles[$role_name];
            $role     = $roles[$role_name];

            $slug        = !empty($raw_role['slug']) ? $raw_role['slug'] : $role->slug;
            $description = !empty($raw_role['description']) ? $raw_role['description'] : null;
            $level       = !empty($raw_role['level']) ? $raw_role['level'] : null;

            $role->slug        = $slug;
            $role->description = $description;
            $role->level       = $level;

            if ($role->isDirty()) {
                $role->save();

                $updated->put($role_name, $role);
            }
        }

        return $updated;
    }

    protected function delete(array $delete_names, Collection $roles)
    {
        $deleted = new Collection();

        foreach ($delete_names as $role_name) {
            $role = $roles[$role_name];
            ($this->class)::where('name', 'like', $role_name)->delete();
            $roles->forget($role_name);

            $deleted->put($role_name, $role);
        }

        return $deleted;
    }

    protected function ignore(Collection $roles, Collection $updated, Collection $deleted)
    {
        $ignored = new Collection();

        $modified = new Collection(array_merge($updated->all(), $deleted->all()));

        foreach ($roles as $role) {
            if (!$modified->has($role->name)) {
                $ignored->put($role->name, $role);
            }
        }

        return $ignored;
    }
}
