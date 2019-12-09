<?php
/**
 * Copyright (c) 2018. Ambroise Maupate and Julien Blanchet
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
 * @file FilterCacheEvent.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */

namespace RZ\Roadiz\Core\Events;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use RZ\Roadiz\Core\Kernel;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class FilterCacheEvent
 *
 * @package RZ\Roadiz\Core\Events
 * @deprecated
 */
abstract class FilterCacheEvent extends Event
{
    /**
     * @var Kernel
     */
    private $kernel;

    /**
     * @var Collection
     */
    private $messageCollection;

    /**
     * @var Collection
     */
    private $errorCollection;

    /**
     * FilterCacheEvent constructor.
     *
     * @param Kernel $kernel
     */
    public function __construct(Kernel $kernel)
    {
        $this->kernel = $kernel;
        $this->messageCollection = new ArrayCollection();
        $this->errorCollection = new ArrayCollection();
    }

    /**
     * @return Kernel
     */
    public function getKernel(): Kernel
    {
        return $this->kernel;
    }

    /**
     * @param string $message
     * @param string|null $classname
     * @param string|null $description
     */
    public function addMessage($message, $classname = null, $description = null)
    {
        $this->messageCollection->add([
            "clearer" => $classname,
            "description" => $description,
            "message" => $message,
        ]);
    }

    /**
     * @param string $message
     * @param string|null $classname
     * @param string|null $description
     */
    public function addError($message, $classname = null, $description = null)
    {
        $this->errorCollection->add([
            "clearer" => $classname,
            "description" => $description,
            "message" => $message,
        ]);
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->errorCollection->toArray();
    }

    /**
     * @return array
     */
    public function getMessages()
    {
        return $this->messageCollection->toArray();
    }
}
