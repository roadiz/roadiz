<?php
/**
 * Copyright © 2018, Ambroise Maupate and Julien Blanchet
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
 * Except as contained in this notice, the name of the roadiz shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from Ambroise Maupate and Julien Blanchet.
 *
 * @file ImageFormatsExtension.php
 * @author Ambroise Maupate
 *
 */

declare(strict_types=1);

namespace Themes\DefaultTheme\Twig;

use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class ImageFormatsExtension extends AbstractExtension implements GlobalsInterface
{
    /**
     * @inheritDoc
     */
    public function getGlobals()
    {
        return [
            'imageFormats' => [
                'headerImage' => [
                    'fit' => '1920x300',
                    'quality' => 85,
                    'picture' => true,
                    'progressive' => true,
                    'class' => 'img-fluid img-responsive',
                ],
                'columnedImage' => [
                    'width' => 720,
                    'picture' => true,
                    'progressive' => true,
                    'class' => 'img-fluid img-responsive',
                ],
                'thumbnail' => [
                    'fit' => '600x338',
                    'controls' => true,
                    'picture' => true,
                    'embed' => true,
                    'progressive' => true,
                    'class' => 'img-fluid img-responsive',
                ],
                'shareImage' => [
                    'fit' => '1200x630',
                    'absolute' => true,
                    'progressive' => true,
                ]
            ]
        ];
    }
}
