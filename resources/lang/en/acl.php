<?php

return [
    'export' => [
        'disabled' => 'Command disabled, check env ACL_EXPORT_ENABLED value or use --force option.',
        'success'  => 'File successfully exported, ":bytes" bytes were written.',
    ],
    'import' => [
        'disabled'        => 'Command disabled, check env ACL_IMPORT_ENABLED value or use --force option.',
        'role'            => [
            'insert' => 'Inserted role ":role_name" with id ":role_id".',
            'update' => 'Updated role ":role_name" with id ":role_id".',
            'delete' => 'Deleted role ":role_name" with id ":role_id".',
            'ignore' => 'Ignored role ":role_name" with id ":role_id".',
        ],
        'permission'      => [
            'insert' => 'Inserted permission ":permission_name" with id ":permission_id".',
            'update' => 'Updated permission ":permission_name" with id ":permission_id".',
            'delete' => 'Deleted permission ":permission_name" with id ":permission_id".',
            'ignore' => 'Ignored permission ":permission_name" with id ":permission_id".',
        ],
        'permission_role' => [
            'insert' => 'For role ":role_name", inserted permission ":permission_name".',
            'delete' => 'For role ":role_name", deleted permission ":permission_name".',
            'ignore' => 'For role ":role_name", ignored permission ":permission_name".',
            'error'  => 'Permission error for role ":role_name", permission ":permission_name" does not exists. Make sure to add it to the acl config permissions section as well.',
        ],
    ],

    'errors'     => [
        'forbidden'                => 'You have not enough permissions to perform this operation.',
        'no_role_model'            => 'Process aborted. There is no Role model.',
        'no_permission_model'      => 'Process aborted. There is no Permission model.',
        'no_roles_table'           => 'Process aborted. There is no roles table.',
        'no_permissions_table'     => 'Process aborted. There is no permissions table.',
        'no_permission_role_table' => 'Process aborted. There is no permission_role table.',
        'no_permission_user_table' => 'Process aborted. There is no permission_user table.',
    ],
    'exceptions' => [
        'config_export_not_allowed' => 'Config can\'t be exported via web inserface. Use artisan command instead.',
    ],
];
