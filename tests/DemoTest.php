<?php

namespace Tests;

use Illuminate\Database\Connection as DB;

class DemoTest extends TestCase
{
    public function testMigrations()
    {
        $real_path = realpath(__DIR__ . '/database/migrations');
        $this->loadMigrationsFrom($real_path);
        $db = $this->app->make(DB::class);
        $schema = $db->getSchemaBuilder();
        $this->assertTrue($schema->hasTable('users'));
        $this->assertTrue($schema->hasTable('roles'));
        $this->assertTrue($schema->hasTable('permissions'));
        $this->assertTrue($schema->hasTable('permission_role'));
        $this->assertTrue($schema->hasTable('permission_user'));
        $db->table('users')->insert(['name' => 'asd', 'email' => 'asd@asd.asd', 'password' => 'asd']);
        $this->assertDatabaseHas('users', ['email' => 'asd@asd.asd']);
    }
}
