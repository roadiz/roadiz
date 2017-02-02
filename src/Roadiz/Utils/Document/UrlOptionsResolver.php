<?php
/**
 * Copyright (c) 2016. Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
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
 * @file UrlOptionsResolver.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */

namespace RZ\Roadiz\Utils\Document;

use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class UrlOptionsResolver
 * @package RZ\Roadiz\Utils\Document
 */
class UrlOptionsResolver extends OptionsResolver
{
    public function __construct()
    {
        $this->setDefaults([
            'crop' => null,
            'fit' => null,
            'background' => null,
            'absolute' => false,
            'grayscale' => false,
            'progressive' => false,
            'noProcess' => false,
            'width' => 0,
            'height' => 0,
            'quality' => 90,
            'blur' => 0,
            'sharpen' => 0,
            'contrast' => 0,
            'rotate' => 0,
        ]);
        $this->setAllowedTypes('width', ['int']);
        $this->setAllowedTypes('height', ['int']);
        $this->setAllowedTypes('crop', ['null', 'string']);
        $this->setAllowedTypes('fit', ['null', 'string']);
        $this->setAllowedTypes('background', ['null', 'string']);
        $this->setAllowedTypes('quality', ['int']);
        $this->setAllowedTypes('blur', ['int']);
        $this->setAllowedTypes('sharpen', ['int']);
        $this->setAllowedTypes('contrast', ['int']);
        $this->setAllowedTypes('rotate', ['int']);
        $this->setAllowedTypes('absolute', ['boolean']);
        $this->setAllowedTypes('grayscale', ['boolean']);
        $this->setAllowedTypes('progressive', ['boolean']);
        $this->setAllowedTypes('noProcess', ['boolean']);

        /*
         * Guess width and height options from fit
         */
        $this->setDefault('width', function (Options $options) {
            if (1 === preg_match('#(?<width>[0-9]+)[x:\.](?<height>[0-9]+)#', $options['fit'], $matches)) {
                return (int) $matches['width'];
            }
            return 0;
        });
        $this->setDefault('height', function (Options $options) {
            if (1 === preg_match('#(?<width>[0-9]+)[x:\.](?<height>[0-9]+)#', $options['fit'], $matches)) {
                return (int) $matches['height'];
            }
            return 0;
        });
    }
}
