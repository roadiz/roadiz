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
 * @file NewsletterHandler.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Handlers;

use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Core\Entities\Newsletter;

/**
 * Handle operations with newsletters entities.
 */
class NewsletterHandler
{
    private $newsletter = null;

    /**
     * @return \RZ\Roadiz\Core\Entities\newsletter
     */
    public function getNewsletter()
    {
        return $this->newsletter;
    }

    /**
     * @param \RZ\Roadiz\Core\Entities\newsletter $newsletter
     *
     * @return $this
     */
    public function setNewsletter($newsletter)
    {
        $this->newsletter = $newsletter;

        return $this;
    }

    /**
     * Create a new newsletter handler with newsletter to handle.
     *
     * @param newsletter $newsletter
     */
    public function __construct(Newsletter $newsletter)
    {
        $this->newsletter = $newsletter;
    }

    /**
     * Duplicate newsletter
     */
    public function duplicate()
    {

        $newNode = $this->newsletter->getNode()->getHandler()->duplicate();
        Kernel::getService('em')->persist($newNode);

        Kernel::getService('em')->refresh($this->newsletter);
        $newsletter = clone $this->newsletter;
        Kernel::getService('em')->persist($newsletter);

        $newsletter->setNode($newNode);

        Kernel::getService('em')->flush();

        return $newsletter;
    }
}
