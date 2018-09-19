<?php

namespace ArtemSchander\L5Modular;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class ModuleServiceProvider extends ServiceProvider
{
    protected $files;
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        if (is_dir(app_path().'/Modules/')) {
            $modules = config("modules.enable") ?: array_map('class_basename', $this->files->directories(app_path().'/Modules/'));
            foreach ($modules as $module) {
                // Allow routes to be cached
                if (!$this->app->routesAreCached()) {
                    if ($this->files->exists(app_path() . '/Modules/' . $module . '/routes/api.php')) {
                        Route::middleware('api')
                            ->namespace("App\Modules\\".$module.'\\Controllers')
                            ->group(app_path() . '/Modules/' . $module . '/routes/api.php');
                    }
                    if ($this->files->exists(app_path() . '/Modules/' . $module . '/routes/web.php')) {
                        Route::middleware('web')
                            ->namespace("App\Modules\\".$module.'\\Controllers')
                            ->group(app_path() . '/Modules/' . $module . '/routes/web.php');
                    }
                    if ($this->files->exists(app_path() . '/Modules/' . $module . '/routes.php')) {
                        Route::middleware('web')
                            ->namespace("App\Modules\\".$module.'\\Controllers')
                            ->group(app_path() . '/Modules/' . $module . '/routes.php');
                    }
                }
                $helper = app_path().'/Modules/'.$module.'/helper.php';
                $views  = app_path().'/Modules/'.$module.'/Views';
                $trans  = app_path().'/Modules/'.$module.'/Translations';

                if ($this->files->exists($helper)) {
                    include_once $helper;
                }
                if ($this->files->isDirectory($views)) {
                    $this->loadViewsFrom($views, $module);
                }
                if ($this->files->isDirectory($trans)) {
                    $this->loadTranslationsFrom($trans, $module);
                }
            }
        }
    }

    public function register()
    {
        $this->files = new Filesystem;
        $this->registerMakeCommand();
    }

    /**
     * Register the "make:module" console command.
     *
     * @return Console\ModuleMakeCommand
     */
    protected function registerMakeCommand()
    {
        $this->commands('modules.make');
        
        $bind_method = method_exists($this->app, 'bindShared') ? 'bindShared' : 'singleton';

        $this->app->{$bind_method}('modules.make', function ($app) {
            return new Console\ModuleMakeCommand($this->files);
        });
    }
}
