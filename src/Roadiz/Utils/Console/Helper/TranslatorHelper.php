<?php
/**
 * Copyright © 2015, Ambroise Maupate and Julien Blanchet
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
 * @file TranslatorHelper.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Utils\Console\Helper;

use Symfony\Component\Translation\Translator;
use Symfony\Component\Console\Helper\Helper;

/**
 * TranslatorHelper.
 */
class TranslatorHelper extends Helper
{
    protected $translator;

    public function __construct(Translator $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'translator';
    }

    /**
     * Translates the given message.
     *
     * Wraps the Translator trans method.
     *
     * @param  string $id
     * @param  array  $parameters
     * @param  null   $domain
     * @param  null   $locale
     *
     * @return string
     */
    public function trans($id, array $parameters = [], $domain = null, $locale = null)
    {
        return $this->translator->trans($id, $parameters, $domain, $locale);
    }


    /**
     * Translates the given message.
     *
     * Wraps the Translator transChoice method.
     *
     * @param  string $id
     * @param  int    $number
     * @param  array  $parameters
     * @param  null   $domain
     * @param  null   $locale
     *
     * @return string
     */
    public function transChoice($id, $number, array $parameters = [], $domain = null, $locale = null)
    {
        return $this->translator->transChoice($id, $number, $parameters, $domain, $locale);
    }
}
