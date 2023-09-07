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

namespace AppSeeds;

use AppSeeds\Bugs\BugAmpAttribute;
use AppSeeds\Bugs\BugCharset;
use AppSeeds\Bugs\BugHtml5DocType;
use AppSeeds\Bugs\BugLongLine;
use AppSeeds\Bugs\BugTemplateScripts;
use AppSeeds\Contracts\PatchInterface;
use AppSeeds\Elements\DocumentNode;
use AppSeeds\Elements\ElementNode;
use AppSeeds\Helpers\DeferJs;
use AppSeeds\Helpers\DeferOptions;

if (date_default_timezone_get() === '' || date_default_timezone_get() === '0') {
    try {
        date_default_timezone_set('GMT');
    } catch (\Exception $exception) {
        unset($exception);
    }
}

if (!defined('DEFER_PHP_ROOT')) {
    define('DEFER_PHP_ROOT', dirname(__DIR__));
}

final class Defer
{
    /**
     * @var DeferOptions
     */
    private $options;

    /**
     * @var DocumentNode|null
     */
    private $document;

    /**
     * Hotfix array.
     *
     * @var array<PatchInterface>
     */
    private $_patchers = [];

    /**
     * Optimized flag.
     *
     * @var bool
     */
    private $_optimized = false;

    /**
     * Store compiled HTML output.
     *
     * @var string|null
     */
    private $_html;

    /**
     * Init Defer instance.
     *
     * @since  2.0.0
     *
     * @param string|array|null $html
     * @param string            $charset
     *
     * @return self
     */
    public function __construct(
        $html = '',
        array $options = null,
        $charset = null
    ) {
        if (is_array($html)) {
            $options = $html;
            $html    = '';
        }

        // Patchers
        $this->_patchers = [
            new BugAmpAttribute(),
            new BugHtml5DocType(),
            new BugTemplateScripts(),
            new BugCharset($charset),
            new BugLongLine(),
        ];

        $this->options = new DeferOptions($options ?: []);
        $this->options->mergeFromRequest();

        $this->fromHtml($html ?: '');
    }

    /**
     * Cleanup memory when destructed.
     *
     * @since  2.0.0
     */
    public function __destruct()
    {
        $this->cleanup();
    }

    /**
     * |-----------------------------------------------------------------------
     * | Main functions
     * |-----------------------------------------------------------------------.
     *
     * @param mixed $html
     */

    /**
     * Load HTML from string.
     *
     * @since  2.0.0
     *
     * @param string $html
     *
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

        if ($this->options()->disabled || !$this->isFullPageHtml($html)) {
            $this->_html = trim($html);
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
     *
     * @return string
     */
    public function toHtml()
    {
        if (empty($this->_html) && !empty($this->document)) {
            $html = $this->document->getHtml();
            $html = $this->patchAfter($html);
            $this->cleanup();
            $this->_html = $html;
            unset($html);
        }

        return $this->_html ?: '';
    }

    /**
     * Optimize the document.
     *
     * @since  2.0.0
     *
     * @return self
     */
    public function optimize()
    {
        // Skip if already _optimized
        if ($this->_optimized) {
            return $this;
        }

        if ($this->document == null) {
            return $this;
        }

        /** @var DocumentNode $dom */
        $dom = $this->document;

        /** @var ElementNode|null $html */
        $html = $dom->root();

        if (empty($html)) {
            return $this;
        }

        $debug = $this->options->debug_mode || $this->options->debug_time;
        $isAmp = $dom->isAmpHtml();

        // Optimize entire document
        $dom->optimize($this->options);

        // Embed defer.js library
        if (!$isAmp) {
            $node = null;
            $lib  = $this->deferjs();

            if ($this->options->manually_add_deferjs) {
                $node = $lib->getInlineGuide($dom)->optimize($this->options);
            } else {
                $lib->cleanDeferTags($dom);

                $node = $this->options->inline_deferjs
                    ? $lib->getInlineScript($dom)
                    : $lib->getDeferJsNode($dom);
            }

            // Optimize the script tag
            if (!empty($node)) {
                $node->optimize($this->options);
                $lib->cleanHelperTags($dom);

                // Debug
                if ($debug) {
                    $node->precede($lib->getDebugJsNode($dom, 'time'));
                    $node->follow($lib->getDebugJsNode($dom, 'timeLog', 'defer.js finished.'));
                    $dom->body()->appendWith($lib->getDebugJsNode($dom, 'timeEnd', 'body finished.'));
                }

                // Append helper CSS
                $node->precede($lib->getHelperCssNode($dom));

                // Append helper script
                $defer_time = $this->options->default_defer_time;
                $copy       = $this->options->console_copyright;
                $node->follow($lib->getHelperJsNode($dom, $defer_time, $copy));

                // Append polyfill
                $node->follow($lib->getPolyfillNode($dom));

                // Custom type for deferred script tags
                if ($this->options->deferjs_type_attribute != '') {
                    $dom->body()->appendWith($lib->getCustomDeferTypeNode(
                        $dom,
                        $this->options->deferjs_type_attribute
                    ));
                }
            }
        }

        // Copyright
        if ($this->options->long_copyright !== '') {
            $copy = trim($this->options->long_copyright, "\t\n\r\0\x0B");
            $node = $dom->createComment(PHP_EOL . $copy . PHP_EOL);
            $html->appendWith($node);
        }

        // Add missing <meta name="meta_generator"> tag
        if ($this->options->copyright !== '') {
            /** @var ElementNode $meta_generator */
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

    /**
     * |-----------------------------------------------------------------------
     * | Other functions
     * |-----------------------------------------------------------------------.
     */

    /**
     * Clean up DOM document.
     *
     * @since  2.0.0
     */
    public function cleanupDocument()
    {
        if ($this->document instanceof DocumentNode) {
            unset($this->document);
            $this->document = null;
        }
    }

    /**
     * Clean up memory.
     *
     * @since  2.0.0
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
     * Get the DeferJs instance.
     *
     * @since  2.0.0
     *
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
     * Get the DeferOptions instance.
     *
     * @since  2.0.0
     *
     * @return DeferOptions
     */
    public function options()
    {
        return $this->options;
    }

    /**
     * Get all options in DeferOptions.
     *
     * @since  2.0.0
     *
     * @return array
     */
    public function optionArray()
    {
        return $this->options->getOptionArray();
    }

    /**
     * |-----------------------------------------------------------------------
     * | Internal functions
     * |-----------------------------------------------------------------------.
     *
     * @param mixed $html
     */

    /**
     * Returns true if the data from $html is a normal HTML document.
     *
     * @since  2.0.0
     *
     * @param string $html
     *
     * @return bool
     */
    private function isFullPageHtml($html)
    {
        return preg_match('/<\!DOCTYPE.+html.+<html/is', substr($html, 0, 1000)) !== false;
    }

    /**
     * Patch the HTML before optimizing.
     *
     * @since  2.0.0
     *
     * @param string $html
     *
     * @return string
     */
    private function patchBefore(&$html)
    {
        foreach ($this->_patchers as $fixer) {
            $html = $fixer->before($html, $this->options);
        }

        return $html;
    }

    /**
     * Patch the HTML after optimizing.
     *
     * @since  2.0.0
     *
     * @param string $html
     *
     * @return string
     */
    private function patchAfter(&$html)
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
