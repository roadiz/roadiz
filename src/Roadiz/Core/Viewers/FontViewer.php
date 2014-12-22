<?php
/**
 * Copyright © 2014, Ambroise Maupate and Julien Blanchet
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
 * @file FontViewer.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Viewers;

use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Core\Entities\Font;
use RZ\Roadiz\Core\Bags\SettingsBag;

use Symfony\Component\Form\Extension\Csrf\CsrfProvider\SessionCsrfProvider;

/**
 * FontViewer
 */
class FontViewer implements ViewableInterface
{
    protected $font = null;
    protected $twig = null;

    /**
     * @param RZ\Roadiz\Core\Entities\Font $font
     */
    public function __construct(Font $font)
    {
        $this->font = $font;
    }

    /**
     * @return Symfony\Component\Translation\Translator
     */
    public function getTranslator()
    {
        return null;
    }

    /**
     * @return \Twig_Environment
     */
    public function getTwig()
    {
        return Kernel::getService('twig.environment');
    }

    /**
     * Get CSS font-face properties for current font.
     *
     * @param Symfony\Component\Form\Extension\Csrf\CsrfProvider\SessionCsrfProvider $csrfProvider
     *
     * @return string CSS output
     */
    public function getCSSFontFace(SessionCsrfProvider $csrfProvider)
    {
        $assignation = array(
            'font' => $this->font,
            'site' => SettingsBag::get('site_name'),
            'fontFolder' => '/'.Font::getFilesFolderName(),
            'csrfProvider' => $csrfProvider
        );

        return $this->getTwig()->render('fonts/fontfamily.css.twig', $assignation);
    }
}
