<?php

/**
 * Defer.php is a PHP library that aims to help you
 * concentrate on webpage performance optimization.
 *
 * Copyright (c) 2022, AppSeeds (https://appseeds.net/)
 *
 * PHP Version >=7.3
 *
 * @package   AppSeeds\Defer
 * @category  core_web_vitals
 * @author    Mai Nhut Tan <shin@shin.company>
 * @copyright Copyright (c) 2022, AppSeeds (https://appseeds.net/)
 * @license   https://code.shin.company/defer.php/blob/master/LICENSE MIT
 * @link      https://code.shin.company/defer.php
 * @example   https://code.shin.company/defer.php/blob/master/README.md
 */

namespace AppSeeds\Defer\Utilities;

use Symfony\Component\OptionsResolver\OptionsResolver;

final class DeferOptions
{
    /**
     * @var mixed[]
     */
    public const DEFAULTS = [
    ];

    /**
     * @var null|OptionsResolver
     */
    private $resolver;

    /**
     * @var array The libarary options
     */
    private $options = [];

    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    */

    public function __construct(array $options = [])
    {
        $this->setOptions($options);
    }

    public function __call($name, $arguments)
    {
        if (false !== preg_match('/^([gs]et)([a-z0-9_])(option)?$/i', $name, $matched)) {
            $callback = [$this, 'set' == strtolower($matched[1]) ? 'setOption' : 'getOption'];
            $name     = $matched[2];
            array_unshift($arguments, $name);

            return call_user_func_array($callback, $arguments);
        }

        return $this;
    }

    public function __get($name)
    {
        return $this->getOption($name);
    }

    public function __set($name, $value)
    {
        return $this->setOption($name, $value);
    }

    /*
    |--------------------------------------------------------------------------
    | Public Methods
    |--------------------------------------------------------------------------
    */

    public function resolver()
    {
        if (empty($this->resolver)) {
            $resolver = new OptionsResolver();
            $resolver->setDefaults(static::DEFAULTS);
            $this->resolver = $resolver;
        }

        return $this->resolver;
    }

    public function setOptions(array $newOptions)
    {
        $this->options = $this->resolver()->resolve($newOptions);

        return $this;
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function setOption($name, $value)
    {
        $newOptions = [];

        if (is_array($name)) {
            $newOptions = $name;
        } elseif (is_string($name)) {
            $name       = $this->normalizeOptionName($name);
            $newOptions = [$name => $value];
        }

        $this->setOptions(array_merge($this->options, $newOptions));

        return $this;
    }

    public function getOption($name = null, $default = null)
    {
        $name = $this->normalizeOptionName($name);

        return $this->options[$name] ?? $default;
    }

    public function hasOption($name = null)
    {
        $name = $this->normalizeOptionName($name);

        return isset($this->options[$name]);
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    private function normalizeOptionName($name)
    {
        if (!ctype_lower($name)) {
            return strtolower(preg_replace('/(.)(?=[A-Z])/u', '$1_', $name));
        }

        return $name;
    }
}
