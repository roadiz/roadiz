<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Events;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\ClassMetadataInfo;

class TablePrefixSubscriber implements EventSubscriber
{
    /**
     * @var string
     */
    protected $tablesPrefix;

    /**
     * @param string $tablesPrefix
     */
    public function __construct(string $tablesPrefix = '')
    {
        $this->tablesPrefix = $tablesPrefix;
    }


    /**
     * @inheritDoc
     */
    public function getSubscribedEvents()
    {
        return [
            Events::loadClassMetadata,
        ];
    }

    /**
     * @param LoadClassMetadataEventArgs $eventArgs
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs)
    {
        /*
         * Prefix tables
         */
        if (!empty($this->tablesPrefix) && $this->tablesPrefix !== '') {
            // the $metadata is all the mapping info for this class
            /** @var ClassMetadataInfo $metadata */
            $metadata = $eventArgs->getClassMetadata();
            $metadata->table['name'] = $this->tablesPrefix.'_'.$metadata->table['name'];

            /*
             * Prefix join tables
             */
            foreach ($metadata->associationMappings as $key => $association) {
                if (!empty($association['joinTable']['name'])) {
                    $metadata->associationMappings[$key]['joinTable']['name'] =
                        $this->tablesPrefix.'_'.$association['joinTable']['name'];
                }
            }
        }
    }
}
