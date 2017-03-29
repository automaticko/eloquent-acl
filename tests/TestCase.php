<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Automaticko\ACL\ACLServiceProvider;
use Orchestra\Database\ConsoleServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected $consoleOutput;

    public function setUp()
    {
        parent::setUp();
        // Setup default database to use sqlite :memory:
        $this->app['config']->set('database.default', 'testbench');
        $this->app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }

    protected function getPackageProviders(Application $app)
    {
        return [ConsoleServiceProvider::class, ACLServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app)
    {
        //$app['config']->set('langman.path', __DIR__.'/temp');
        //$app['config']->set('view.paths', [__DIR__.'/views_temp']);
    }

    /*
    public function setUp()
    {
        parent::setUp();


    public function tearDown()
    {
        parent::tearDown();

        exec('rm -rf '.__DIR__.'/temp/*');

        $this->consoleOutput = '';
    }

    public function createTempFiles($files = [])
    {
        foreach ($files as $dir => $dirFiles) {
            mkdir(__DIR__.'/temp/'.$dir);

            foreach ($dirFiles as $file => $content) {
                if (is_array($content)) {
                    mkdir(__DIR__.'/temp/'.$dir.'/'.$file);

                    foreach ($content as $subDir => $subContent) {
                        mkdir(__DIR__.'/temp/vendor/'.$file.'/'.$subDir);
                        foreach ($subContent as $subFile => $subsubContent) {
                            file_put_contents(__DIR__.'/temp/'.$dir.'/'.$file.'/'.$subDir.'/'.$subFile.'.php', $subsubContent);
                        }
                    }
                } else {
                    file_put_contents(__DIR__.'/temp/'.$dir.'/'.$file.'.php', $content);
                }
            }
        }
    }

    public function resolveApplicationConsoleKernel($app)
    {
        $app->singleton('artisan', function ($app) {
            return new \Illuminate\Console\Application($app, $app['events'], $app->version());
        });

        $app->singleton('Illuminate\Contracts\Console\Kernel', Kernel::class);
    }

    public function artisan($command, $parameters = [])
    {
        parent::artisan($command, array_merge($parameters, ['--no-interaction' => true]));
    }

    public function consoleOutput()
    {
        return $this->consoleOutput ?: $this->consoleOutput = $this->app[Kernel::class]->output();
    }
    */
}
