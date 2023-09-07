<?php

/**
 * Defer.php aims to help you concentrate on web performance optimization.
 * (c) 2019-2023 SHIN Company https://shin.company
 *
 * PHP Version >=5.6
 *
 * @category  Web_Performance_Optimization
 * @package   AppSeeds
 * @author    Mai Nhut Tan <shin@shin.company>
 * @copyright 2019-2023 SHIN Company
 * @license   https://code.shin.company/defer.php/blob/master/LICENSE MIT
 * @link      https://code.shin.company/defer.php
 * @see       https://code.shin.company/defer.php/blob/master/README.md
 */

namespace AppSeeds\Helpers;

use AppSeeds\Elements\DocumentNode;
use AppSeeds\Elements\ElementNode;

if (!defined('DEFER_PHP_ROOT')) {
    define('DEFER_PHP_ROOT', dirname(dirname(__DIR__)));
}

final class DeferJs
{
    /**
     * @var string
     */
    const DEFERJS_ID = 'defer-js';

    /**
     * @var string
     */
    const POLYFILL_ID = 'polyfill-js';

    /**
     * @var string
     */
    const HELPERS_JS = 'defer-script';

    /**
     * @var string
     */
    const HELPERS_CSS = 'defer-css';

    /**
     * @var string
     */
    private $deferjs_src;

    /**
     * @var string
     */
    private $polyfill_src;

    /**
     * @var DeferCache
     */
    private $_cache;

    /**
     * @param string $deferjs_src
     * @param string $polyfill_src
     * @param string $offline_cache_path
     * @param int    $offline_cache_ttl
     */
    public function __construct(
        $deferjs_src,
        $polyfill_src,
        $offline_cache_path,
        $offline_cache_ttl
    ) {
        $this->deferjs_src  = $deferjs_src;
        $this->polyfill_src = $polyfill_src;

        $this->_cache = new DeferCache([
            'path'       => $offline_cache_path ?: DeferConstant::SCR_DEFERJS_CACHE,
            'defaultTtl' => (int) $offline_cache_ttl ?: 86400,
        ]);
    }

    /**
     * |-----------------------------------------------------------------------
     * | DOM related functions
     * |-----------------------------------------------------------------------.
     *
     * @param mixed $dom
     */

    /**
     * Remove all defer helpers from DocumentNode.
     *
     * @param DocumentNode $dom
     *
     * @return DocumentNode
     */
    public function cleanDeferTags(&$dom)
    {
        $tags = implode(',', [
            'script#' . self::DEFERJS_ID,
            'script#' . self::POLYFILL_ID,
        ]);

        $dom->find($tags)->detach();

        return $dom;
    }

    /**
     * Remove all defer helpers from DocumentNode.
     *
     * @param DocumentNode $dom
     *
     * @return DocumentNode
     */
    public function cleanHelperTags(&$dom)
    {
        $tags = implode(',', [
            'script#' . self::HELPERS_JS,
            'style#' . self::HELPERS_CSS,
        ]);

        $dom->find($tags)->detach();

        return $dom;
    }

    /**
     * Return new inline <script> node.
     *
     * @param DocumentNode $dom
     *
     * @return ElementNode
     */
    public function getInlineScript($dom)
    {
        $defer = $this->isLocal($this->deferjs_src)
        ? @file_get_contents($this->deferjs_src)
        : $this->getFromCache();

        return $dom->newNode('script', $defer ?: '', [
            'id' => self::DEFERJS_ID,
        ]);
    }

    /**
     * Return new inline <script> node.
     *
     * @param DocumentNode $dom
     *
     * @return ElementNode
     */
    public function getInlineGuide($dom)
    {
        static $message;

        if (!isset($message)) {
            $message = sprintf(
                DeferConstant::TEMPLATE_MANUALLY_ADD_DEFER,
                implode('\n', [
                    'You should manually add the defer.js.',
                    'Like this:',
                    strtr(
                        $this->getDeferJsNode($dom)->getOuterHtml(),
                        ['/' => '\/', "'" => '\\\'', '\"' => '\\"', "\n" => '\n']
                    ),
                ])
            );

            $message = DeferMinifier::minifyJs($message);
        }

        return $dom->newNode('script', $message, ['id' => self::DEFERJS_ID]);
    }

    /**
     * Return new <script src="defer.js"> node.
     *
     * @param DocumentNode $dom
     *
     * @return ElementNode
     */
    public function getDeferJsNode($dom)
    {
        // Fallback to inline script when a local path given
        if (!$this->isWebUrl($this->deferjs_src)) {
            return $this->getInlineScript($dom);
        }

        return $dom->newNode('script', [
            'id'  => self::DEFERJS_ID,
            'src' => $this->deferjs_src,
        ]);
    }

    /**
     * Return new <script> node with debug information.
     *
     * @param string|null  $method
     * @param string|null  $message
     * @param DocumentNode $dom
     *
     * @return ElementNode
     */
    public function getDebugJsNode($dom, $method = 'time', $message = '')
    {
        $label = 'defer.js perf';

        if ($message) {
            $message = strtr($message, ["'" => "\\'"]);
            $message = sprintf(";console.info('%s: %s')", $label, $message);
        }

        $script = sprintf("try{console.%s('%s')%s}finally{}", $method, $label, $message);

        return $dom->newNode('script', $script);
    }

    /**
     * Return polyfill script node.
     *
     * @param DocumentNode $dom
     *
     * @return ElementNode|null
     */
    public function getPolyfillNode($dom)
    {
        if ($this->isWebUrl($this->polyfill_src)) {
            $script = "'IntersectionObserver'in window||"
                        . "document.write('<script src=\"" . $this->polyfill_src . "\"><\\/script>');";
        } elseif ($this->isLocal($this->polyfill_src)) {
            $script = @file_get_contents($this->polyfill_src);
        }

        return empty($script) ? null : $dom->newNode('script', $script, ['id' => self::POLYFILL_ID]);
    }

    /**
     * Return helper script node.
     *
     * @param int          $default_defer_time
     * @param string|null  $copy
     * @param DocumentNode $dom
     *
     * @return ElementNode|null
     */
    public function getHelperJsNode(
        $dom,
        $default_defer_time = null,
        $copy = null
    ) {
        static $script;

        if (!isset($script)) {
            $script = @file_get_contents(DEFER_PHP_ROOT . '/public/helpers.min.js');

            if ($default_defer_time > 0) {
                $script = 'var ' . DeferConstant::JS_GLOBAL_DELAY_VAR
                    . '=' . $default_defer_time . ';' . $script;
            }
        }

        if ($script && $copy) {
            $copy   = "['" . strtr($copy, ["\n" => '\n', "\r" => '\n', "'" => '\\\'']) . "']";
            $script = preg_replace('#\[\'Optimized[^\]]+\]#i', $copy, $script);
        }

        return empty($script) ? null : $dom->newNode('script', $script, ['id' => self::HELPERS_JS]);
    }

    /**
     * Return helper script node.
     *
     * @param DocumentNode $dom
     *
     * @return ElementNode|null
     */
    public function getHelperCssNode($dom)
    {
        static $content;

        if (!isset($content)) {
            $content = @file_get_contents(DEFER_PHP_ROOT . '/public/styles.min.css');
        }

        return empty($content) ? null : $dom->newNode('style', $content, ['id' => self::HELPERS_CSS]);
    }

    /**
     * Get script when using custom defer type.
     *
     * @param string       $type
     * @param DocumentNode $dom
     *
     * @return ElementNode|null
     */
    public function getCustomDeferTypeNode($dom, $type)
    {
        if ($type == DeferConstant::TXT_DEFAULT_DEFERJS) {
            return null;
        }

        $script = sprintf('Defer.all(\'script[type="%s"]\')', $type);

        return $dom->newNode('script', $script);
    }

    /**
     * |-----------------------------------------------------------------------
     * | Helper methods
     * |-----------------------------------------------------------------------.
     *
     * @param mixed $path
     */

    /**
     * Check a path is an URL.
     *
     * @since  2.0.0
     *
     * @param string $path
     *
     * @return bool
     */
    public function isWebUrl($path)
    {
        return (bool) preg_match('/^(https?:)?\/\//i', $path);
    }

    /**
     * Check a path is a local path.
     *
     * @since  2.0.0
     *
     * @param string $path
     *
     * @return bool
     */
    public function isLocal($path)
    {
        return file_exists($path);
    }

    /**
     * Get cache instance.
     *
     * @since  2.0.0
     *
     * @return DeferCache
     */
    public function cache()
    {
        return $this->_cache;
    }

    /**
     * Get cache key.
     *
     * @since  2.0.0
     *
     * @return string
     */
    public function cacheKey()
    {
        return gethostname() . '@' . $this->deferjs_src;
    }

    /**
     * |-----------------------------------------------------------------------
     * | Static functions
     * |-----------------------------------------------------------------------.
     */

    /**
     * Get deferjs from cache.
     *
     * @since  2.0.0
     */
    public function getFromCache()
    {
        $key = $this->cacheKey();

        if (!$this->cache()->has($key)) {
            $defer = $this->makeOffline($key);

            if (empty($defer)) {
                $defer = @file_get_contents(DeferConstant::SRC_DEFERJS_FALLBACK);

                if (!empty($defer)) {
                    return $defer;
                }

                throw new DeferException('Could not load defer.js library! Please check your configuration.');
            }
        }

        return $this->cache()->get($key);
    }

    /**
     * Create fully local version of external assets
     * For General Data Protection Regulation GDPR (DSGVO).
     *
     * @since  2.0.0
     *
     * @param string|null $key
     * @param int         $duration
     *
     * @return string|false
     */
    public function makeOffline($key = null, $duration = 9999999999)
    {
        if (empty($this->deferjs_src)) {
            return false;
        }

        $defer = @file_get_contents($this->deferjs_src);

        if (empty($defer)) {
            return false;
        }

        $defer = '/*!' . $this->deferjs_src . '*/'
                . PHP_EOL . DeferMinifier::minifyJs($defer);

        $this->cache()->set($key ?: $this->cacheKey(), $defer, $duration);

        return $defer;
    }

    /**
     * Static method to purge all cached objects in DeferCache.
     *
     * @since  2.0.0
     *
     * @param string|null $key
     *
     * @return $this
     */
    public function purgeOffline($key = null)
    {
        $this->cache()->delete($key ?: $this->cacheKey());

        return $this;
    }
}
