<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Forms\Extension;

use Pimple\Container;
use Symfony\Component\Form\FormExtensionInterface;

/**
 * Loads FormType from Pimple Container to enable
 * passing arguments to form constructor.
 *
 * @package RZ\Roadiz\CMS\Forms\Extension
 */
final class ContainerFormExtension implements FormExtensionInterface
{
    protected Container $container;

    /**
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function getType($name)
    {
        return $this->container->offsetGet($name);
    }

    public function hasType($name)
    {
        return $this->container->offsetExists($name);
    }

    public function getTypeExtensions($name)
    {
        return [];
    }

    public function hasTypeExtensions($name)
    {
        return false;
    }

    public function getTypeGuesser()
    {
        return null;
    }
}
