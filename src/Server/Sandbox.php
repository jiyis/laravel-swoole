<?php

namespace Jiyis\Server;


use Illuminate\Container\Container;
use Jiyis\Illuminate\Application;
use Illuminate\Support\Facades\Facade;
use Laravel\Lumen\Application as LumenApplication;

class Sandbox
{
    /**
     * @var \Jiyis\Illuminate\Application
     */
    protected $application;

    /**
     * @var \Jiyis\Illuminate\Application
     */
    protected $snapshot;

    /**
     * @var boolean
     */
    public $enabled = false;

    /**
     * Make a sandbox.
     * @param Application $application
     * @return Sandbox
     */
    public static function make(Application $application)
    {
        return new static($application);
    }

    /**
     * Sandbox constructor.
     * @param Application $application
     */
    public function __construct(Application $application)
    {
        $this->setApplication($application);
    }

    /**
     * Set a base application
     *
     * @param \Jiyis\Illuminate\Application
     */
    public function setApplication(Application $application)
    {
        $this->application = $application;
    }

    /**
     * Get an application snapshot
     * @return Application
     * @throws \ReflectionException
     */
    public function getApplication()
    {
        if ($this->snapshot instanceOf Application) {
            return $this->snapshot;
        }

        $snapshot = clone $this->application;
        $this->resetLaravelApp($snapshot->getApplication());

        return $this->snapshot = $snapshot;
    }

    /**
     * Reset Laravel/Lumen Application.
     * @param $application
     * @throws \ReflectionException
     */
    protected function resetLaravelApp($application)
    {
        if ($this->isFramework('laravel')) {
            $application->bootstrapWith([
                'Illuminate\Foundation\Bootstrap\LoadConfiguration'
            ]);
        } elseif ($this->isFramework('lumen')) {
            $reflector = new \ReflectionMethod(LumenApplication::class, 'registerConfigBindings');
            $reflector->setAccessible(true);
            $reflector->invoke($application);
        } else {

        }

        $this->rebindRouterContainer($application);
        $this->rebindViewContainer($application);
    }

    /**
     *  Rebind laravel's container in router.
     * @param $application
     */
    protected function rebindRouterContainer($application)
    {
        if ($this->isFramework('laravel')) {
            $router = $application->make('router');
            $closure = function () use ($application) {
                $this->container = $application;
            };

            $resetRouter = $closure->bindTo($router, $router);
            $resetRouter();
        } elseif ($this->isFramework('lumen')) {
            // lumen router only exists after lumen 5.5
            if (property_exists($application, 'router')) {
                $application->router->app = $application;
            }
        }
    }

    /**
     *  Rebind laravel/lumen's container in view.
     * @param $application
     */
    protected function rebindViewContainer($application)
    {
        $view = $application->make('view');

        $closure = function () use ($application) {
            $this->container = $application;
            $this->shared['app'] = $application;
        };

        $resetView = $closure->bindTo($view, $view);
        $resetView();
    }

    /**
     * Get application's framework.
     * @param string $name
     * @return bool
     */
    protected function isFramework(string $name)
    {
        return $this->application->getFramework() === $name;
    }

    /**
     * Get a laravel snapshot
     * @return Container
     * @throws \ReflectionException
     */
    public function getLaravelApp()
    {
        if ($this->snapshot instanceOf Application) {
            return $this->snapshot->getApplication();
        }

        return $this->getApplication()->getApplication();
    }

    /**
     * Set laravel snapshot to container and facade.
     */
    public function enable()
    {
        if (is_null($this->snapshot)) {
            $this->getApplication($this->application);
        }

        $this->setInstance($this->getLaravelApp());
        $this->enabled = true;
    }

    /**
     * Set original laravel app to container and facade.
     */
    public function disable()
    {
        if (! $this->enabled) {
            return;
        }

        if ($this->snapshot instanceOf Application) {
            $this->snapshot = null;
        }

        $this->setInstance($this->application->getApplication());
    }

    /**
     *  Replace app's self bindings.
     * @param $application
     */
    protected function setInstance($application)
    {
        $application->instance('app', $application);
        $application->instance(Container::class, $application);

        if ($this->isFramework('lumen')) {
            $application->instance(LumenApplication::class, $application);
        }

        Container::setInstance($application);
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($application);
    }
}