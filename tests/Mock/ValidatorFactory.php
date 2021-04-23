<?php

namespace Tests\Mock;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Validation\Factory as BaseValidatorFactory;
use Illuminate\Translation\Translator;
use Illuminate\Translation\FileLoader;

class ValidatorFactory
{
    private BaseValidatorFactory $factory;
    
    public function __construct()
    {
        $this->factory = new BaseValidatorFactory(
            $this->loadTranslator()
        );
    }

    private function loadTranslator()
    {
        $filesystem = new Filesystem();

        $langdir = realpath(__DIR__ . '/../../lang');

        $loader = new FileLoader($filesystem, $langdir);
        $loader->addNamespace('lang', $langdir);
        $loader->load('en', 'validation', 'lang');

        return new Translator($loader, 'en');
    }

    public function __call($method, $args)
    {
        return call_user_func_array(
            [$this->factory, $method],
            $args
        );
    }
}
