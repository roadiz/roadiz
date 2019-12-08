<?php
declare(strict_types=1);
/**
 * Copyright © 2016, Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * Except as contained in this notice, the name of the ROADIZ shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from Ambroise Maupate and Julien Blanchet.
 *
 * @file ParsedownExtension.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Utils\TwigExtensions;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Class ParsedownExtension
 *
 * @package RZ\Roadiz\Utils\TwigExtensions
 * @deprecated Use \RZ\Roadiz\Markdown\MarkdownExtension
 */
class ParsedownExtension extends AbstractExtension
{
    /**
     * @var \Parsedown
     */
    protected $parsedown;
    /**
     * @var \ParsedownExtra
     */
    protected $parsedownExtra;

    public function __construct()
    {
        $this->parsedown = new \Parsedown();
        $this->parsedownExtra = new \ParsedownExtra();
    }

    public function getFilters()
    {
        return [
            new TwigFilter('markdown', [$this, 'markdown'], ['is_safe' => ['html']]),
            new TwigFilter('inlineMarkdown', [$this, 'inlineMarkdown'], ['is_safe' => ['html']]),
            new TwigFilter('markdownExtra', [$this, 'markdownExtra'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * @param string $text
     * @return string
     */
    public function markdown($text)
    {
        return $this->parsedown->text($text);
    }

    /**
     * @param string $text
     * @return string
     */
    public function inlineMarkdown($text)
    {
        return $this->parsedown->line($text);
    }

    /**
     * @param string $text
     * @return string
     */
    public function markdownExtra($text)
    {
        /*
         * Need to recreate the object to reset
         * footnotes count.
         *
         * https://github.com/erusev/parsedown-extra/issues/97
         */
        $this->parsedownExtra = new \ParsedownExtra();
        return $this->parsedownExtra->text($text);
    }
}
