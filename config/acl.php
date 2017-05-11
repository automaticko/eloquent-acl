<?php

return [
    /*
    |--------------------------------------------------------------------------
    | ACL enabled
    |--------------------------------------------------------------------------
    |
    | This value dictates if ACL is enforced. Independently of ACL middleware
    | being used, whenever that route is requested and ACL start checking,
    | if this value is false, ACL will allow that route to be processed.
    */

    'enabled' => env('ACL_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Role and Permission Models
    |--------------------------------------------------------------------------
    |
    | The models listed here are defined in package Ultraware/Roles package
    | and point to the exact class for each. Feel free to use whichever
    | files / namespaces, but make sure to extend the original ones,
    | or at least, copy / paste the traits and methods defined.
    |
    */

    'models' => [
        'permission' => Ultraware\Roles\Models\Permission::class,
        'role'       => Ultraware\Roles\Models\Role::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    |
    | Determines if cache is going to be used to store and load permissions
    | Ttl indicates how many minutes permissions will remain in cache
    | Permissions key indicates the key used to cache permissions
    */

    'cache'  => [
        'enabled'         => true,
        'ttl'             => 60,
        'permissions_key' => 'automaticko.acl.permissions',

    ],

    /*
    |--------------------------------------------------------------------------
    | Import
    |--------------------------------------------------------------------------
    |
    | This section manages acl:import artisan command behavior and act as such.
    | If enabled is not true, the acl:import command won't do anything.
    |
    | The permissions section defines permissions to be persisted in database.
    | Permission properties slug, model and description are not required.
    | If no slug is provided, it will be generated using name.
    |
    | The roles section defines which roles are to be persisted into database.
    | Properties slug, model, description and permissions are all optional.
    | If there is no slug property, the slug will be generated using name.
    | Permissions subsection should contain a list of permission names.
    | These names need to have been defined within the permissions.
    |
    */
    'import' => [
        'enabled'     => env('ACL_IMPORT_ENABLED', true),
        'permissions' => [
            'role.edit' => [ // Name, must follow same naming route notation
                'slug'        => 'edit-role',
                'model'       => Ultraware\Roles\Models\Role::class,
                'description' => 'Example permission',
            ],
        ],
        'roles'       => [
            'super_admin' => [ // Name
                'slug'        => 'super-admin',
                'level'       => 1,
                'description' => 'System Administrator',
                'permissions' => [
                    'role.edit',
                ],
            ],
            'admin'       => [
                'slug'        => 'admin',
                'level'       => 2,
                'description' => 'App Administrator',
                'permissions' => [
                    'home.index',
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Export
    |--------------------------------------------------------------------------
    |
    | This section determines if acl:export artisan command will be usable
    | If enabled is not true, the acl:export command won't do anything.
    | If enabled is true, this configuration file will be overwritten
    | with permissions and roles data coming from database.
    |
    */
    'export' => [
        'enabled' => env('ACL_EXPORT_ENABLED', true),
    ],
];
