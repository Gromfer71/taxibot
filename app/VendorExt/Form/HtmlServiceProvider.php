<?php
namespace App\VendorExt\Form;

class HtmlServiceProvider extends \Collective\Html\HtmlServiceProvider
{

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerHtmlBuilder();

        $this->registerFormBuilder();

        //$this->app->alias('html', 'Illuminate\Html\HtmlBuilder');
        $this->app->alias('html', 'Collective\Html\HtmlBuilder');
        //$this->app->alias('form', 'Illuminate\Html\FormBuilder');
        $this->app->alias('form', 'App\VendorExt\Form\FormBuilder');
    }

    /**
     * Register the form builder instance.
     *
     * @return void
     */
    protected function registerFormBuilder()
    {
        $this->app->singleton('form', function ($app) {
            $form = new FormBuilder($app['html'], $app['url'], $app['view'], $app['session.store']->getToken());

            return $form->setSessionStore($app['session.store']);
        });
    }
}