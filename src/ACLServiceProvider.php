<?php
/**
 * Laravel ACL
 *
 * @author    Automaticko <automaticko@gmail.com>
 * @copyright 2017 Automaticko
 * @license   http://www.opensource.org/licenses/mit-license.php MIT
 * @link      https://github.com/automaticko/acl
 */

namespace Automaticko\ACL;

use Illuminate\Routing\Router;
use Automaticko\ACL\Middleware\ACL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\Repository as Cache;
use Ultraware\Roles\RolesServiceProvider;
use Ultraware\Roles\Middleware\VerifyRole;
use Illuminate\Contracts\Auth\Access\Gate;
use Ultraware\Roles\Middleware\VerifyLevel;
use Illuminate\Config\Repository as Config;
use Automaticko\ACL\Console\Commands\Export;
use Automaticko\ACL\Console\Commands\Import;
use Illuminate\Contracts\Foundation\Application;
use Ultraware\Roles\Middleware\VerifyPermission;

class ACLServiceProvider extends ServiceProvider
{
    protected $app;

    protected $config;

    public function __construct(Application $app)
    {
        parent::__construct($app);

        $this->app    = $app;
        $this->config = $app->make(Config::class);
    }

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadTranslations();

        $this->publishConfig();

        $this->publishMigrations();

        $this->commands([Import::class, Export::class]);

        $this->aliasMiddlewares();

        $this->loadPermissions();
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        // Registers ultraware/roles service provider so there is no need to do it manually
        $this->app->register(RolesServiceProvider::class);

        $this->mergeConfig();

        // Load ultraware/roles migrations so there is no need to publish them
        $this->loadMigrations();
    }

    protected function loadTranslations()
    {
        $lang_path = __DIR__ . '/../resources/lang';
        $this->loadTranslationsFrom($lang_path, 'acl');
    }

    protected function mergeConfig()
    {
        $config_path = __DIR__ . '/../config/acl.php';
        $this->mergeConfigFrom($config_path, 'acl');
    }

    protected function publishConfig()
    {
        $config_path  = __DIR__ . '/../config/acl.php';
        $publish_path = base_path('config/acl.php');
        $this->publishes([$config_path => $publish_path], 'config');
    }

    protected function loadMigrations()
    {
        $migrations_path = base_path('vendor/ultraware/roles/migrations');
        $this->loadMigrationsFrom($migrations_path);
    }

    protected function publishMigrations()
    {
        $ultraware_migrations_path = base_path('vendor/ultraware/roles/migrations');
        $migrations_path           = base_path('/database/migrations');

        $this->publishes([$ultraware_migrations_path => $migrations_path], 'migrations');
    }

    protected function aliasMiddlewares()
    {
        $route = $this->app->make(Router::class);

        $route->aliasMiddleware('ACL', ACL::class);

        // These belong to ultraware/roles package
        $route->aliasMiddleware('role', VerifyRole::class);
        $route->aliasMiddleware('permission', VerifyPermission::class);
        $route->aliasMiddleware('level', VerifyLevel::class);
    }

    protected function loadPermissions()
    {
        if (!$this->config->get('acl.enabled')) {
            return [];
        }

        if ($this->app->runningInConsole() && !$this->app->runningUnitTests()) {
            return [];
        }

        $gate = $this->app->make(Gate::class);
        foreach ($this->getPermissions() as $permission) {
            $gate->define($permission->name,
                function ($user) use ($permission) {
                    return $user->hasRole($permission->roles);
                });
        }
    }

    protected function getPermissions()
    {
        $permission_class = $this->config->get('acl.models.permission');

        if ($this->config->get('acl.cache.enabled')) {
            $permissions = $this->getCachedPermissions($permission_class);
        } else {
            $permissions = $permission_class::with('roles')->get();
        }

        return $permissions;
    }

    protected function getCachedPermissions($permission_class)
    {
        $cache = $this->app->make(Cache::class);

        $permissions_key = $this->config->get('acl.cache.permissions_key');
        $ttl             = $this->config->get('acl.cache.ttl');

        if (!$cache->has($permissions_key)) {
            $cache->add($permissions_key, $permission_class::with('roles')->get(), $ttl);
        }

        return $cache->get($permissions_key);
    }
}
