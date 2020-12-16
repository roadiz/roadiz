<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Forms\DataTransformer;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ObjectManager;
use RZ\Roadiz\Core\AbstractEntities\AbstractEntity;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * @package RZ\Roadiz\CMS\Forms\DataTransformer
 */
class EntityCollectionTransformer implements DataTransformerInterface
{
    /**
     * @var bool
     */
    protected $asCollection;
    /**
     * @var ObjectManager
     */
    private $manager;
    /**
     * @var class-name|string
     */
    private $classname;

    /**
     * @param ObjectManager $manager
     * @param class-name $classname
     * @param bool $asCollection
     */
    public function __construct(ObjectManager $manager, string $classname, bool $asCollection = false)
    {
        $this->manager = $manager;
        $this->asCollection = $asCollection;
        $this->classname = $classname;
    }

    /**
     * @param ArrayCollection<AbstractEntity>|AbstractEntity[]|null $entities
     * @return string|array
     */
    public function transform($entities)
    {
        if (null === $entities || empty($entities)) {
            return '';
        }
        $ids = [];
        /** @var AbstractEntity $entity */
        foreach ($entities as $entity) {
            $ids[] = $entity->getId();
        }
        if ($this->asCollection) {
            return $ids;
        }
        return implode(',', $ids);
    }

    /**
     * @param string|array|null $entityIds
     * @return array<AbstractEntity>|ArrayCollection<AbstractEntity>
     */
    public function reverseTransform($entityIds)
    {
        if (!$entityIds) {
            if ($this->asCollection) {
                return new ArrayCollection();
            }
            return [];
        }

        if (is_array($entityIds)) {
            $ids = $entityIds;
        } else {
            $ids = explode(',', $entityIds);
        }

        $entities = [];
        foreach ($ids as $entityId) {
            $entity = $this->manager
                ->getRepository($this->classname)
                ->find($entityId)
            ;
            if (null === $entity) {
                throw new TransformationFailedException(sprintf(
                    'A %s with id "%s" does not exist!',
                    $this->classname,
                    $entityId
                ));
            }

            $entities[] = $entity;
        }
        if ($this->asCollection) {
            return new ArrayCollection($entities);
        }
        return $entities;
    }
}
