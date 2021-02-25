<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Console\Helper;

use RZ\Roadiz\Core\AbstractEntities\AbstractEntity;
use RZ\Roadiz\Core\Handlers\AbstractHandler;
use RZ\Roadiz\Core\Handlers\HandlerFactory;
use Symfony\Component\Console\Helper\Helper;

/**
 * @package RZ\Roadiz\Utils\Console\Helper
 */
class HandlerFactoryHelper extends Helper
{
    protected HandlerFactory $handlerFactory;

    /**
     * @param HandlerFactory $handlerFactory
     */
    public function __construct(HandlerFactory $handlerFactory)
    {
        $this->handlerFactory = $handlerFactory;
    }

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return 'handlerFactory';
    }

    /**
     * @param AbstractEntity $entity
     * @return AbstractHandler
     */
    public function getHandler(AbstractEntity $entity): AbstractHandler
    {
        return $this->handlerFactory->getHandler($entity);
    }

    /**
     * @return HandlerFactory
     */
    public function getHandlerFactory(): HandlerFactory
    {
        return $this->handlerFactory;
    }
}
