<?php

/**
 * A PHP helper class to efficiently defer JavaScript for your website.
 * (c) 2019 AppSeeds https://appseeds.net/
 *
 * @package   shinsenter/defer.php
 * @since     1.0.0
 * @author    Mai Nhut Tan <shin@shin.company>
 * @copyright 2019 AppSeeds
 * @see       https://github.com/shinsenter/defer.php/blob/develop/README.md
 */

namespace shinsenter\Helpers;

/*
 * JsMin.php - modified PHP implementation of Douglas Crockford's JsMin.
 *
 * <code>
 * $minifiedJs = JsMin::minify($js);
 * </code>
 *
 * This is a modified port of jsmin.c. Improvements:
 *
 * Does not choke on some regexp literals containing quote characters. E.g. /'/
 *
 * Spaces are preserved after some add/sub operators, so they are not mistakenly
 * converted to post-inc/dec. E.g. a + ++b -> a+ ++b
 *
 * Preserves multi-line comments that begin with /*!
 *
 * PHP 5 or higher is required.
 *
 * Permission is hereby granted to use this version of the library under the
 * same terms as jsmin.c, which has the following license:
 *
 * --
 * Copyright (c) 2002 Douglas Crockford  (www.crockford.com)
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of
 * this software and associated documentation files (the "Software"), to deal in
 * the Software without restriction, including without limitation the rights to
 * use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies
 * of the Software, and to permit persons to whom the Software is furnished to do
 * so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * The Software shall be used for Good, not Evil.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 * --
 *
 * @author Ryan Grove <ryan@wonko.com> (PHP port)
 * @author Steve Clay <steve@mrclay.org> (modifications + cleanup)
 * @author Andrea Giammarchi <http://www.3site.eu> (spaceBeforeRegExp)
 * @copyright 2002 Douglas Crockford <douglas@crockford.com> (jsmin.c)
 * @copyright 2008 Ryan Grove <ryan@wonko.com> (PHP port)
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @see http://code.google.com/p/jsmin-php/
 */

use Exception;

class JsMinException extends Exception
{
}

class JsMin
{
    const ORD_LF            = 10;
    const ORD_SPACE         = 32;
    const ACTION_KEEP_A     = 1;
    const ACTION_DELETE_A   = 2;
    const ACTION_DELETE_A_B = 3;

    protected $a            = '';
    protected $b            = '';
    protected $input        = '';
    protected $inputIndex   = 0;
    protected $inputLength  = 0;
    protected $lookAhead;

    protected $output = '';

    /**
     * Constructor
     *
     * @param string $input Javascript to be minified
     */
    public function __construct($input)
    {
        $this->input       = str_replace("\r\n", "\n", $input);
        $this->inputLength = strlen($this->input);
    }

    // -- Public Static Methods --------------------------------------------------

    /**
     * Minify Javascript
     *
     * @uses __construct()
     * @uses min()
     * @param  string $js Javascript to be minified
     * @return string
     */
    public static function minify($js)
    {
        $jsmin = new JsMin($js);

        return $jsmin->min();
    }

    // -- Protected Instance Methods ---------------------------------------------

    /**
     * Action -- do something! What to do is determined by the $command argument.
     *
     * action treats a string as a single character. Wow!
     * action recognizes a regular expression if it is preceded by ( or , or =.
     *
     * @uses next()
     * @uses get()
     * @param  int            $command One of class constants:
     *                                 ACTION_KEEP_A      Output A. Copy B to A. Get the next B.
     *                                 ACTION_DELETE_A    Copy B to A. Get the next B. (Delete A).
     *                                 ACTION_DELETE_A_B  Get the next B. (Delete B).
     * @throws JsMinException If parser errors are found:
     *                                - Unterminated string literal
     *                                - Unterminated regular expression set in regex literal
     *                                - Unterminated regular expression literal
     */
    protected function action($command)
    {
        switch ($command) {
            case self::ACTION_KEEP_A:
                $this->output .= $this->a;
            // no break
            case self::ACTION_DELETE_A:
                $this->a = $this->b;

                if ($this->a === "'" || $this->a === '"') {
                    for (;;) {
                        $this->output .= $this->a;
                        $this->a = $this->get();

                        if ($this->a === $this->b) {
                            break;
                        }

                        if (ord($this->a) <= self::ORD_LF) {
                            throw new JsMinException('Unterminated string literal.');
                        }

                        if ($this->a === '\\') {
                            $this->output .= $this->a;
                            $this->a = $this->get();
                        }
                    }
                }
            // no break
            case self::ACTION_DELETE_A_B:
                $this->b = $this->next();

                if ($this->b === '/' && (
                    $this->a === '(' || $this->a === ',' || $this->a === '=' ||
                    $this->a === ':' || $this->a === '[' || $this->a === '!' ||
                    $this->a === '&' || $this->a === '|' || $this->a === '?' ||
                    $this->a === '{' || $this->a === '}' || $this->a === ';' ||
                    $this->a === "\n"
                )) {
                    $this->output .= $this->a . $this->b;

                    for (;;) {
                        $this->a = $this->get();

                        if ($this->a === '[') {
                            /*
                            inside a regex [...] set, which MAY contain a '/' itself. Example: mootools Form.Validator near line 460:
                            return Form.Validator.getValidator('IsEmpty').test(element) || (/^(?:[a-z0-9!#$%&'*+/=?^_`{|}~-]\.?){0,63}[a-z0-9!#$%&'*+/=?^_`{|}~-]@(?:(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)*[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?|\[(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\])$/i).test(element.get('value'));
                             */
                            for (;;) {
                                $this->output .= $this->a;
                                $this->a = $this->get();

                                if ($this->a === ']') {
                                    break;
                                }

                                if ($this->a === '\\') {
                                    $this->output .= $this->a;
                                    $this->a = $this->get();
                                } elseif (ord($this->a) <= self::ORD_LF) {
                                    throw new JsMinException('Unterminated regular expression set in regex literal.');
                                }
                            }
                        } elseif ($this->a === '/') {
                            break;
                        } elseif ($this->a === '\\') {
                            $this->output .= $this->a;
                            $this->a = $this->get();
                        } elseif (ord($this->a) <= self::ORD_LF) {
                            throw new JsMinException('Unterminated regular expression literal.');
                        }
                        $this->output .= $this->a;
                    }
                    $this->b = $this->next();
                }
        }
    }

    /**
     * Get next char. Convert ctrl char to space.
     *
     * @return null|string
     */
    protected function get()
    {
        $c               = $this->lookAhead;
        $this->lookAhead = null;

        if ($c === null) {
            if ($this->inputIndex < $this->inputLength) {
                $c = substr($this->input, $this->inputIndex, 1);
                $this->inputIndex++;
            } else {
                $c = null;
            }
        }

        if ($c === "\r") {
            return "\n";
        }

        if ($c === null || $c === "\n" || ord($c) >= self::ORD_SPACE) {
            return $c;
        }

        return ' ';
    }

    /**
     * Is $c a letter, digit, underscore, dollar sign, or non-ASCII character.
     *
     * @param  mixed $c
     * @return bool
     */
    protected function isAlphaNum($c)
    {
        return ord($c) > 126 || $c === '\\' || preg_match('/^[\w\$]$/', $c) === 1;
    }

    /**
     * Perform minification, return result
     *
     * @uses action()
     * @uses isAlphaNum()
     * @uses get()
     * @uses peek()
     * @return string
     */
    protected function min()
    {
        if (0 == strncmp($this->peek(), "\xef", 1)) {
            $this->get();
            $this->get();
            $this->get();
        }
        $this->a = "\n";
        $this->action(self::ACTION_DELETE_A_B);
        while ($this->a !== null) {
            switch ($this->a) {
                case ' ':
                    if ($this->isAlphaNum($this->b)) {
                        $this->action(self::ACTION_KEEP_A);
                    } else {
                        $this->action(self::ACTION_DELETE_A);
                    }
                    break;
                case "\n":
                    switch ($this->b) {
                        case '{':
                        case '[':
                        case '(':
                        case '+':
                        case '-':
                        case '!':
                        case '~':
                            $this->action(self::ACTION_KEEP_A);
                            break;
                        case ' ':
                            $this->action(self::ACTION_DELETE_A_B);
                            break;
                        default:
                            if ($this->isAlphaNum($this->b)) {
                                $this->action(self::ACTION_KEEP_A);
                            } else {
                                $this->action(self::ACTION_DELETE_A);
                            }
                    }
                    break;
                default:
                    switch ($this->b) {
                        case ' ':
                            if ($this->isAlphaNum($this->a)) {
                                $this->action(self::ACTION_KEEP_A);
                                break;
                            }
                            $this->action(self::ACTION_DELETE_A_B);
                            break;
                        case "\n":
                            switch ($this->a) {
                                case '}':
                                case ']':
                                case ')':
                                case '+':
                                case '-':
                                case '"':
                                case "'":
                                    $this->action(self::ACTION_KEEP_A);
                                    break;
                                default:
                                    if ($this->isAlphaNum($this->a)) {
                                        $this->action(self::ACTION_KEEP_A);
                                    } else {
                                        $this->action(self::ACTION_DELETE_A_B);
                                    }
                            }
                            break;
                        default:
                            $this->action(self::ACTION_KEEP_A);
                            break;
                    }
            }
        }

        return $this->output;
    }

    /**
     * Get the next character, skipping over comments. peek() is used to see
     *  if a '/' is followed by a '/' or '*'.
     *
     * @uses get()
     * @uses peek()
     * @throws JsMinException on unterminated comment
     * @return string
     */
    protected function next()
    {
        $c = $this->get();

        if ($c === '/') {
            switch ($this->peek()) {
                case '/':
                    for (;;) {
                        $c = $this->get();

                        if (ord($c) <= self::ORD_LF) {
                            return $c;
                        }
                    }
                // no break
                case '*':
                    $this->get();

                    for (;;) {
                        switch ($this->get()) {
                            case '*':
                                if ($this->peek() === '/') {
                                    $this->get();

                                    return ' ';
                                }
                                break;
                            case null:
                                throw new JsMinException('Unterminated comment.');
                        }
                    }
                // no break
                default:
                    return $c;
            }
        }

        return $c;
    }

    /**
     * Get next char. If is ctrl character, translate to a space or newline.
     *
     * @uses get()
     * @return null|string
     */
    protected function peek()
    {
        $this->lookAhead = $this->get();

        return $this->lookAhead;
    }
}
