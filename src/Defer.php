<?php

/**
 * Defer.php aims to help you concentrate on web performance optimization.
 * (c) 2021 AppSeeds https://appseeds.net/
 *
 * PHP Version >=5.6
 *
 * @category  Web_Performance_Optimization
 * @package   AppSeeds
 * @author    Mai Nhut Tan <shin@shin.company>
 * @copyright 2021 AppSeeds
 * @license   https://code.shin.company/defer.php/blob/master/LICENSE MIT
 * @link      https://code.shin.company/defer.php
 * @see       https://code.shin.company/defer.php/blob/master/README.md
 */

namespace AppSeeds;

use AppSeeds\Bugs\BugAmpAttribute;
use AppSeeds\Bugs\BugCharset;
use AppSeeds\Bugs\BugHtml5DocType;
use AppSeeds\Bugs\BugLongLine;
use AppSeeds\Bugs\BugTemplateScripts;
use AppSeeds\Elements\DocumentNode;
use AppSeeds\Helpers\DeferJs;
use AppSeeds\Helpers\DeferOptions;

if (!defined('DEFER_PHP_ROOT')) {
    if (!ini_get('date.timezone')) {
        date_default_timezone_set('GMT');
    }

    define('DEFER_PHP_ROOT', dirname(dirname(__FILE__)));
}

class Defer
{
    protected $options;
    protected $document;

    /**
     * Hotfix array
     * @property bool $_patchers
     */
    private $_patchers = [];

    /**
     * Optimized flag
     * @property bool $_optimized
     */
    private $_optimized = false;

    /**
     * Store compiled HTML output
     * @property string $_html
     */
    private $_html;

    /**
     * Init Defer instance
     *
     * @since  2.0.0
     * @param  mixed $html
     * @return self
     */
    public function __construct(
        $html = '',
        array $options = null,
        string $charset = null
    ) {
        if (is_array($html)) {
            $options = $html;
            $html    = '';
        }

        // Patchers
        $this->_patchers = [
            new BugAmpAttribute(),
            new BugHtml5DocType(),
            new BugCharset($charset),
            new BugLongLine(),
            new BugTemplateScripts(),
        ];

        $this->options = new DeferOptions($options ?: []);

        $this->fromHtml($html);
    }

    /**
     * Cleanup memory when destructed
     *
     * @since  2.0.0
     */
    public function __destruct()
    {
        $this->cleanup();
    }

    /*
    |--------------------------------------------------------------------------
    | Main functions
    |--------------------------------------------------------------------------
     */

    /**
     * Load HTML from string
     *
     * @since  2.0.0
     * @param  string $html
     * @return self
     */
    public function fromHtml($html)
    {
        // Check if gc_enable is true
        $gc_enabled = @gc_enabled();

        // Turn on gc_enable
        if (!$gc_enabled) {
            @gc_enable();
        }

        $this->cleanup();

        if ($this->options()->disable || !$this->isFullPageHtml($html)) {
            $this->_html = $html;
        } else {
            $this->document                      = new DocumentNode('1.0', 'UTF-8');
            $this->document->formatOutput        = true;
            $this->document->preserveWhiteSpace  = false;
            $this->document->recover             = true;
            $this->document->strictErrorChecking = false;
            $this->document->validateOnParse     = false;
            $this->document->setHtml($this->patchBefore($html));
            $this->optimize();
        }

        // Restore original gc_enable setting
        if (!$gc_enabled) {
            @gc_disable();
        }

        return $this;
    }

    /**
     * Returns _optimized HTML content
     * With debug_mode = true, this only returns the optmized tags.
     *
     * @since  2.0.0
     * @return string
     */
    public function toHtml()
    {
        if (empty($this->_html)) {
            $html = $this->document->getHtml();
            $html = $this->patchAfter($html);
            $this->cleanup();
            $this->_html = $html;
            unset($html);
        }

        return $this->_html;
    }

    /**
     * Optimize the document
     *
     * @since  2.0.0
     * @return self
     */
    public function optimize()
    {
        $dom  = $this->document;
        $html = $dom->root();

        // Skip if already _optimized
        if ($this->_optimized || empty($html)) {
            return $this;
        }

        // Optimize entire document
        $dom->optimize($this->options);

        // Embed defer.js library
        if (!$dom->isAmpHtml()) {
            $node = null;
            $lib  = $this->deferjs();

            if ($this->options->manually_add_deferjs) {
                $node = $lib->getInlineGuide($dom, true)->optimize($this->options);
            } else {
                $lib->cleanDeferTags($dom);

                if ($this->options->inline_deferjs) {
                    $node = $lib->getInlineScript($dom, true);
                } else {
                    $node = $lib->getDeferJsNode($dom, true);
                }
            }

            // Optimize the script tag
            if (!empty($node)) {
                $node->optimize($this->options);
                $lib->cleanHelperTags($this->document);

                // Append helper CSS
                $node->precede($lib->getHelperCssNode($this->document));

                // Append helper script
                $defer_time = $this->options->default_defer_time;
                $node->follow($lib->getHelperJsNode($this->document, $defer_time));

                // Append polyfill
                $node->follow($lib->getPolyfillNode($this->document));

                // Custom type for deferred script tags
                if ($this->options->deferjs_type_attribute != '') {
                    $dom->body()->appendWith($lib->getCustomDeferTypeNode(
                        $this->document,
                        $this->options->deferjs_type_attribute
                    ));
                }
            }
        }

        // Copyright
        if ($this->options->long_copyright) {
            $copy = trim($this->options->long_copyright, "\t\n\r\0\x0B");
            $node = $dom->createComment(PHP_EOL . $copy . PHP_EOL);
            $html->appendWith($node);
        }

        // Add missing <meta name="meta_generator"> tag
        if ($this->options->copyright) {
            $meta_generator = $html->find('meta[name="generator"]')->first();

            if ($meta_generator == null) {
                $meta_generator = $dom->newNode('meta', [
                    'name'    => 'generator',
                    'content' => trim($this->options->copyright),
                ]);
            } else {
                $meta_generator->detach();
            }

            $dom->head()->appendWith($meta_generator);
        }

        // Update _optimized flag
        $this->_optimized = true;

        return $this;
    }

    /*
    |--------------------------------------------------------------------------
    | Other functions
    |--------------------------------------------------------------------------
     */

    /**
     * Clean up DOM document
     *
     * @since  2.0.0
     * @return void
     */
    public function cleanupDocument()
    {
        unset($this->document);
        $this->document = null;
    }

    /**
     * Clean up memory
     *
     * @since  2.0.0
     * @return void
     */
    public function cleanup()
    {
        // Reset cached html
        $this->_html = null;

        // Turn off _optimized flag
        $this->_optimized = false;

        // Release DOM data
        $this->cleanupDocument();
    }

    /**
     * Get the DeferJs instance
     *
     * @since  2.0.0
     * @return DeferJs
     */
    public function deferjs()
    {
        return new DeferJs(
            $this->options->deferjs_src,
            $this->options->polyfill_src,
            $this->options->offline_cache_path,
            $this->options->offline_cache_ttl
        );
    }

    /**
     * Get the DeferOptions instance
     *
     * @since  2.0.0
     * @return DeferOptions
     */
    public function options()
    {
        return $this->options;
    }

    /**
     * Get all options in DeferOptions
     *
     * @since  2.0.0
     * @return array
     */
    public function optionArray()
    {
        return $this->options->getOptionArray();
    }

    /*
    |--------------------------------------------------------------------------
    | Internal functions
    |--------------------------------------------------------------------------
     */

    /**
     * Returns true if the data from $html is an normal HTML document
     *
     * @since  2.0.0
     * @param  string $html
     * @return bool
     */
    protected function isFullPageHtml($html)
    {
        return strstr($html, '<html') !== false
            && strstr($html, '</html>') !== false;
    }

    /**
     * Patch the HTML before optimizing
     *
     * @since  2.0.0
     * @param  string $html
     * @return string
     */
    protected function patchBefore(&$html)
    {
        foreach ($this->_patchers as $fixer) {
            $html = $fixer->before($html, $this->options);
        }

        return $html;
    }

    /**
     * Patch the HTML after optimizing
     *
     * @since  2.0.0
     * @param  string $html
     * @return string
     */
    protected function patchAfter(&$html)
    {
        $patchers = array_reverse($this->_patchers);

        foreach ($patchers as $fixer) {
            $html = $fixer->after($html, $this->options);
            $fixer->cleanup();
            unset($fixer);
        }

        return $html;
    }
}
