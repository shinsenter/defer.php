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

namespace AppSeeds;

use AppSeeds\Defer\Utilities\DeferOptions;
use AppSeeds\Defer\Utilities\DeferParser;
use Performance\Lib\Handlers\PerformanceHandler;
use Performance\Performance;

/**
 * @mixin DeferOptions;
 * @mixin DeferParser;
 * @mixin PerformanceHandler;
 */
final class Defer
{
    /*
    |--------------------------------------------------------------------------
    | Properties
    |--------------------------------------------------------------------------
    */

    /**
     * @var DeferOptions|null
     */
    private $options;

    /**
     * @var DeferParser|null
     */
    private $parser;

    /**
     * @var PerformanceHandler|null
     */
    private $profiler;

    /*
    |--------------------------------------------------------------------------
    | Initial variables
    |--------------------------------------------------------------------------
    */

    /**
     * @var bool
     */
    private $isAmp = false;

    /**
     * @var mixed[] Array of node types to be optimized
     */
    private $targets = [];

    /**
     * @var mixed[] Array of common issues to be patched
     */
    private $patches = [];

    /*
    |--------------------------------------------------------------------------
    | Constructor and Destructor
    |--------------------------------------------------------------------------
    */
    /**
     * @param string $content
     */
    public function __construct(array $options = [], $content = null)
    {
        $this->options  = new DeferOptions($options);
        $this->parser   = new DeferParser($content, $this->options->current_uri);
        $this->profiler = class_exists(Performance::class) ? Performance::instance() : null;
    }

    public function __destruct()
    {
        if ($this->parser !== null) {
            $this->parser->clear();
            $this->parser = null;
        }

        if ($this->profiler !== null) {
            $this->profiler = null;
        }

        @gc_collect_cycles();
    }

    public function __call($name, $arguments)
    {
        $proxy = null;

        if ($this->parser && method_exists($this->parser, $name)) {
            $proxy = [$this->parser, $name];
        }

        if ($this->profiler && method_exists($this->profiler, $name)) {
            $proxy = [Performance::class, $name];
        }

        if ($this->options && method_exists($this->options, $name)) {
            $proxy = [$this->options, $name];
        }

        if (empty($proxy)) {
            return $this;
        }

        return call_user_func_array($proxy, $arguments);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessor Methods
    |--------------------------------------------------------------------------
    */

    public function options()
    {
        return $this->options;
    }

    /*
    |--------------------------------------------------------------------------
    | Optimization Methods
    |--------------------------------------------------------------------------
    */

    public function optimize()
    {
        // TODO: add logic

        return $this;
    }
}
