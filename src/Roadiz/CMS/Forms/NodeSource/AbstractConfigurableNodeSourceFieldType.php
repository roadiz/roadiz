<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Forms\NodeSource;

use Symfony\Component\Yaml\Yaml;

/**
 * @package RZ\Roadiz\CMS\Forms\NodeSource
 */
abstract class AbstractConfigurableNodeSourceFieldType extends AbstractNodeSourceFieldType
{
    /**
     * @param array $options
     *
     * @return mixed
     */
    protected function getFieldConfiguration(array $options)
    {
        return Yaml::parse($options['nodeTypeField']->getDefaultValues() ?? '');
    }
}
