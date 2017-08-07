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
 * @file TranslationHandler.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Handlers;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\ORM\objectManagerInterface;
use RZ\Roadiz\Core\Entities\Translation;

/**
 * Handle operations with translations entities.
 */
class TranslationHandler extends AbstractHandler
{
    /**
     * @var Translation
     */
    private $translation;

    /**
     * @return Translation
     */
    public function getTranslation()
    {
        return $this->translation;
    }

    /**
     * @param Translation $translation
     *
     * @return $this
     */
    public function setTranslation(Translation $translation)
    {
        $this->translation = $translation;
        return $this;
    }

    /**
     * Set current translation as default one.
     *
     * @return $this
     */
    public function makeDefault()
    {
        $defaults = $this->objectManager
            ->getRepository('RZ\Roadiz\Core\Entities\Translation')
            ->findBy(['defaultTranslation'=>true]);

        /** @var Translation $default */
        foreach ($defaults as $default) {
            $default->setDefaultTranslation(false);
        }
        $this->objectManager->flush();
        $this->translation->setDefaultTranslation(true);
        $this->objectManager->flush();

        if ($this->objectManager instanceof objectManagerInterface) {
            $cacheDriver = $this->objectManager->getConfiguration()->getResultCacheImpl();
            if ($cacheDriver !== null && $cacheDriver instanceof CacheProvider) {
                $cacheDriver->deleteAll();
            }
        }

        return $this;
    }
}
