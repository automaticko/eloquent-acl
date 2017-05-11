# Eloquent ACL for Laravel 5.4

Simple package that enforces Access Control List using Eloquent.

- [Overview](#overview)
    - [What is does](#what-it-does)
    - [What does not](#what-does-not)
- [Installation](#installation)
    - [Requirements](#requirements)
    - [Composer](#composer)
    - [Service Provider](#service-provider)
    - [Config File](#config-file)
    - [Environment variables](#environment-variables)
    - [Migrations](#migrations)
- [Usage](#usage)
    - [Middlewares](#middlewares)
- [Console Commands](#console-commands)
    - [Import](#import)
    - [Export](#export)
- [Config File](#config-file)
- [More Information](#more-information)
- [Contributing](#contributing)
- [License](#license)

## Overview

Many, many times, it was a good decision to name routes after views. It allowed me to easily identify which view
should be called in a certain controller method, it also, worked the opposite way, while exploring the views,
in most cases i could easily identify which controller was loading that view.

Using a similar logic, this package works by naming permissions same as these routes and views.
The basic idea is to load all permissions from cache or database on each request and attach each a closure that checks
if the requesting user has at least a role that contains this permission. Then, when a user requests a protected by acl
middleware route, two checks are performed.
- The requesting user is authenticated.
- The named route, whose name is the same as the permission name (slug property in fact),
is part of the loaded permissions, and if so, if the authenticated user has a role that contains the requested permission.

If any of these checks fail, a "permission denied" page or message is returned.

### What it does

Allows a basic ACL to be performed in a "per role" level on routes.

This means, when any user requests an ACL protected route, the route name will need to exists in any of the requesting
user roles in order to allow access.  

### What does not

This ACL does not intend to provide a "per user" protection to route.
You can achieve that by using the [Ultraware/Roles](https://github.com/ultraware/roles#middleware) middlewares
or by using policies.

## Installation

Make sure to read all the process in order to correctly install this package.

### Requirements

ACL requires
- Laravel 5.4
- Ultraware/Roles package

### Ultraware/Roles

There is no need to follow the installation process on Ultraware/Roles package if not already done.

Any installation steps needed are covered in this guide.

Make sure to check [Ultraware/Roles](https://github.com/ultraware/roles) package documentation for usage guide.

### Composer

Pull this package in through Composer 
```
composer require automaticko/acl
```

### Service Provider

After updating composer, add the service provider to the `providers` array in `config/app.php`

```php
Automaticko\ACL\ACLServiceProvider::class,
```
```php
'providers' => [
    
    ...
    
    /**
     * Third Party Service Providers...
     */
    Automaticko\ACL\ACLServiceProvider::class,

],
```

### Config File

You can publish the package config file to your application.

Run these commands inside your terminal.

    php artisan vendor:publish --provider="Automaticko\ACL\ACLServiceProvider" --tag=config
    
Make sure to check Ultraware [Ultraware/Roles](https://github.com/ultraware/roles) package documentation for publish options.

###Environment variables
There are 3 environment variables used in the acl config file (config file doesn't need to be published in order for these to work).

Variable| Default value | Description
-------------------|---------------|------------
ACL_ENABLED| True | Enable acl middleware
ACL_IMPORT_ENABLED| True|Enable acl:import command
ACL_EXPORT_ENABLED| True|Enable acl:export command

### Migrations

[Ultraware/Roles](https://github.com/ultraware/roles) package offers the way to publish its migrations, although this is not
needed nor recommended by this package.

In order to run the [Ultraware/Roles](https://github.com/ultraware/roles) migrations just run

    php artisan migrate

> This uses the default users table which is in Laravel.
You should already have the migration file for the users table available and migrated.

### HasRoleAndPermission Trait And Contract

As per [Ultraware/Roles](https://github.com/ultraware/roles) package documentation, you should
include `HasRoleAndPermission` trait and also implement `HasRoleAndPermission` contract inside your `User` model.

## Usage



### Middlewares

This package uses `ACL` middleware. Any route that need to be ACL protected, should use it or belong to a group of
routes that uses it.

Although [Ultraware/Roles](https://github.com/ultraware/roles) middlewares can be used, these are not needed.
In order to use these, there is no need, but also no harm, in register [Ultraware/Roles](https://github.com/ultraware/roles#middleware) 
middlewares.

## Config File

The config file contains several sections and values that are required for acl to correctly work.
Many of the options there are self explanatory or have a comment that will help you understand and modify it as
you need. The `permissions`, `roles`

## Console Commands

The ACL package provides commands to import and export roles and permissions from a config file to database and
vice versa.

### Import

It happened to me that in many situations, while and app was already deployed in a production environment, the client
asked for a specific rol/permission to be added or a new feature which included one or many roles/permissions, or simply
a route that previously had a permission or none now needed to have a different one.
Sometimes, in these cases, creating a migration to insert these permissions/roles or modifying routes middleware was
not something wanted/could do.

Import command was created for these situations.
It does NOT intend to replace the process of inserting/updating/deleting roles/permissions but to just give
a relatively easy way to do it.

When this command run, the `import` section of the `config/acl.php` file is loaded and permissions and roles are
inserted, updated or deleted from db.

The following statement applies to permissions or roles
- If its present in `config/acl.php` but its not present in db, is inserted.
- If its present in `config/acl.php` and is present in db, is updated if has any change.
- If is not present in `config/acl.php` and is present in db, is deleted from db.

In the same section, `import`, inside the subsection `roles`, each role should contain a `permissions` section
which should list all the permissions belonging to that role.
For each of these roles permissions.
- If is present in `config/acl.php` `permissions` section and not in db, is inserted into db creating a relation
between the current role and permission.
- If is present in `config/acl.php` `permissions` section and already in db, is ignored.
- If not is present in `config/acl.php` `permissions` section (at this point the permission was deleted from db if existed),
is ignored and a warning is displayed by the command telling you about it.

The command to run is

    php artisan acl:import --force --v
    
The --force and --v options are optional
- --force do as it says and forces the import, ignoring the `config/acl.php` `import.enabled` value.
- --v makes the command more verbose. 

### Export

Sometimes as well, i was developing a new feature that required many permissions/roles, which i had
inserted/updated/deleted from db, to be present on production.
Tinkering with db in production was not what i felt safe, so the `export` command was created.
Again, this command does NOT intend to replace the process of inserting/updating/deleting roles/permissions but
to just give a relatively easy way to do it.

The `export` command does the opposite of the `import` command. It reads database and write permission, roles and permission
roles into `config/acl.php` overwriting it but respecting the values it previously had.
Any statement stored in `config/acl.php` will be converted to the value that holds at the moment the command
is run.

In example. If `config/acl.php` contains

```php
'enabled' => !(1 * 1),
...
```

The file will be generated containing
```php
'enabled' => false,
...
```
Which is the value of `!(1 * 1)`

In this way, you can push this config file to production and run the `import` command there to update permissions/roles.

## More Information

For more information, please have a look at
[ACLServiceProvider](https://github.com/automaticko/acl/blob/master/src/ACLServiceProvider.php) class and
[ACL](https://github.com/automaticko/acl/blob/master/src/Middleware/ACL.php) middleware.

## Contributing

There are no tests so far. PRs are welcome.

## License

This package is free software distributed under the terms of the MIT license.
