<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Importers;

use Doctrine\ORM\EntityManager;
use RZ\Roadiz\Core\Handlers\HandlerFactoryInterface;

/**
 * Class for create all importer.
 *
 * @deprecated
 */
interface ImporterInterface
{
    /**
     * Import json file.
     *
     * @param string $template
     * @param EntityManager $em
     * @param HandlerFactoryInterface $handlerFactory
     * @return bool
     * @deprecated
     */
    public static function importJsonFile($template, EntityManager $em, HandlerFactoryInterface $handlerFactory);
}
