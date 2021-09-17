<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Events;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @package RZ\Roadiz\Core\Events
 */
abstract class FilterCacheEvent extends Event
{
    private KernelInterface $kernel;

    /**
     * @var Collection
     */
    private $messageCollection;

    /**
     * @var Collection
     */
    private $errorCollection;

    /**
     * @param KernelInterface $kernel
     */
    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
        $this->messageCollection = new ArrayCollection();
        $this->errorCollection = new ArrayCollection();
    }

    /**
     * @return KernelInterface
     */
    public function getKernel(): KernelInterface
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
