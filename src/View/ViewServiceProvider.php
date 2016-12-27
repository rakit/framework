<?php 

namespace Rakit\Framework\View;

use Rakit\Framework\App;
use Rakit\Framework\Http\Response;
use Rakit\Framework\Provider;
use Rakit\Framework\View\View;

class ViewServiceProvider extends Provider {

    public function register()
    {
        $app = $this->app;
        $app['view:Rakit\Framework\View\View'] = $app->container->singleton(function($container) use ($app) {
            $view_path = $app->config->get('view.path');
            $engine = $app->config->get('view.engine');

            if(!$engine) {
                $engine = new BasicViewEngine($view_path);
            } elseif(is_string($engine)) {
                $engine = $container->make($engine, [$view_path]);
            }

            return new View($container['app'], $engine);
        });
    }

    public function boot()
    {
        $app = $this->app;
        $app->response->macro('view', function($file, array $data = array()) use ($app) {
            $rendered = $app->view->render($file, $data);
            return $app->response->html($rendered);
        });
    }

}
