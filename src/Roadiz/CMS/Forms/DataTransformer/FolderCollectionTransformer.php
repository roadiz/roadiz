<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Forms\DataTransformer;

use Doctrine\Persistence\ObjectManager;
use RZ\Roadiz\Core\Entities\Folder;

/**
 * @package RZ\Roadiz\CMS\Forms\DataTransformer
 */
class FolderCollectionTransformer extends EntityCollectionTransformer
{
    /**
     * @param ObjectManager $manager
     * @param bool $asCollection
     */
    public function __construct(ObjectManager $manager, bool $asCollection = false)
    {
        parent::__construct($manager, Folder::class, $asCollection);
    }
}
