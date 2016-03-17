<?php
/**
 * Copyright (c) Rezo Zero 2016.
 *
 * prison-insider
 *
 * Created on 17/03/16 12:28
 *
 * @author ambroisemaupate
 * @file ParsedownExtension.php
 */

namespace RZ\Roadiz\Utils\TwigExtensions;

class ParsedownExtension extends \Twig_Extension
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

    public function getName()
    {
        return 'parsedownExtension';
    }

    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('markdown', [$this, 'markdown'], ['is_safe' => ['html']]),
            new \Twig_SimpleFilter('inlineMarkdown', [$this, 'inlineMarkdown'], ['is_safe' => ['html']]),
            new \Twig_SimpleFilter('markdownExtra', [$this, 'markdownExtra'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * @param string $text
     * @return string
     */
    public function markdown($text)
    {
        return $this->parsedow->text($text);
    }

    /**
     * @param string $text
     * @return string
     */
    public function inlineMarkdown($text)
    {
        return $this->parsedow->line($text);
    }

    /**
     * @param string $text
     * @return string
     */
    public function markdownExtra($text)
    {
        return $this->parsedownExtra->text($text);
    }
}
