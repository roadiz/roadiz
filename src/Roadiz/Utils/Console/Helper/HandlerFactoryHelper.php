<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Console\Helper;

use RZ\Roadiz\Core\AbstractEntities\AbstractEntity;
use RZ\Roadiz\Core\Handlers\HandlerFactory;
use Symfony\Component\Console\Helper\Helper;

/**
 * Class HandlerFactoryHelper
 * @package RZ\Roadiz\Utils\Console\Helper
 */
class HandlerFactoryHelper extends Helper
{
    /**
     * @var HandlerFactory
     */
    private $handlerFactory;

    /**
     * HandlerFactoryHelper constructor.
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
     * @return \RZ\Roadiz\Core\Handlers\AbstractHandler
     */
    public function getHandler(AbstractEntity $entity)
    {
        return $this->handlerFactory->getHandler($entity);
    }

    /**
     * @return HandlerFactory
     */
    public function getHandlerFactory()
    {
        return $this->handlerFactory;
    }
}
