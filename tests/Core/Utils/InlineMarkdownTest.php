<?php

use RZ\Renzo\Core\Utils\InlineMarkdown;
use RZ\Renzo\Core\Kernel;

class InlineMarkdownTest extends PHPUnit_Framework_TestCase
{

    /**
     * @dataProvider defaultTransformProvider
     */
    public function testDefaultTransform($markdown, $html)
    {
        $this->assertEquals($html, InlineMarkdown::defaultTransform($markdown));
    }

    public static function defaultTransformProvider()
    {
        return array(
            array("Hello *world*", "Hello <em>world</em>"),
            array("**Hello *world***", "<strong>Hello <em>world</em></strong>"),
            array("## Hello *world*", "## Hello <em>world</em>"),
            array("**Hello *[world](#1)***", "<strong>Hello <em><a href=\"#1\">world</a></em></strong>"),
            array("Hello [world](#1)
Mollis Fusce", "Hello <a href=\"#1\">world</a>
Mollis Fusce")
        );
    }
}