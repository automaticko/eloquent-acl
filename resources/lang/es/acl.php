<?php

return [
    'export' => [
        'disabled' => 'Comando inhabilitado, revise el valor de ACL_EXPORT_ENABLED en el archivo .env o use la opción --force.',
        'success'  => 'Archivo exportado satisfactoriamente, ":bytes" bytes fueron escritos.',
    ],
    'import' => [
        'disabled'        => 'Comando inhabilitado, revise el valor de ACL_IMPORT_ENABLED en el archivo .env o use la opción --force.',
        'role'            => [
            'insert' => 'Se insertó el rol ":role_name", con id ":role_id".',
            'update' => 'Se editó el rol ":role_name", con id ":role_id".',
            'delete' => 'Se elimintó el rol ":role_name" con id ":role_id".',
            'ignore' => 'Se ignoró el rol ":role_name" con id ":role_id".',
        ],
        'permission'      => [
            'insert' => 'Se insertó el permiso ":permission_name", con id ":permission_id".',
            'update' => 'Se editó el permiso ":permission_name", con id ":permission_id".',
            'delete' => 'Se elimintó el permiso ":permission_name" con id ":permission_id".',
            'ignore' => 'Se ignoró el permiso ":permission_name" con id ":permission_id".',
        ],
        'permission_role' => [
            'insert' => 'Rol ":role_name", se insertó el permiso ":permission_name".',
            'delete' => 'Rol ":role_name", se eliminó el permiso ":permission_name".',
            'ignore' => 'Rol ":role_name", se ignoró el permiso ":permission_name".',
            'error'  => 'Error en permiso para el rol ":role_name", el permiso ":permission_name" no existe. Asegúrese de agregarlo a la configuración de acl en la sección de permisos.',
        ],
    ],

    'errors'     => [
        'forbidden'                => 'No tienes permisos suficientes para realizar esta acción.',
        'no_role_model'            => 'Proceso abortado. No se encuentra el modelo Role.',
        'no_permission_model'      => 'Proceso abortado. No se encuentra el modelo Permission.',
        'no_roles_table'           => 'Proceso abortado. No se encuentra la tabla roles.',
        'no_permissions_table'     => 'Proceso abortado. No se encuentra la tabla permissions.',
        'no_permission_role_table' => 'Proceso abortado. No se encuentra la tabla permission_role.',
        'no_permission_user_table' => 'Proceso abortado. No se encuentra la tabla permission_user.',
    ],
    'exceptions' => [
        'config_export_not_allowed' => 'No se puede exportar la configuración via web. Utilice el comando de artisan en su lugar.',
    ],
];
