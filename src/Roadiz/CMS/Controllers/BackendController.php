<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Controllers;

/**
 * Special controller app file for backend themes.
 *
 * This AppController implementation will use a security scheme
 */
abstract class BackendController extends AppController
{
    protected static $backendTheme = true;

    /**
     * {@inheritdoc}
     */
    public static $priority = -10;

    /**
     * @inheritDoc
     */
    public function createEntityListManager($entity, array $criteria = [], array $ordering = [])
    {
        return parent::createEntityListManager($entity, $criteria, $ordering)
            ->setDisplayingNotPublishedNodes(true);
    }
}
