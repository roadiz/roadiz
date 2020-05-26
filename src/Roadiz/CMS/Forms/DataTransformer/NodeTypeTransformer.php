<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Forms\DataTransformer;

use Doctrine\Persistence\ObjectManager;
use RZ\Roadiz\Core\Entities\NodeType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * Class NodeTypeTransformer
 * @package RZ\Roadiz\CMS\Forms\DataTransformer
 */
class NodeTypeTransformer implements DataTransformerInterface
{
    private $manager;

    /**
     * NodeTypeTransformer constructor.
     * @param ObjectManager $manager
     */
    public function __construct(ObjectManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @param NodeType $nodeType
     * @return int|string
     */
    public function transform($nodeType)
    {
        if (null === $nodeType) {
            return '';
        }
        return $nodeType->getId();
    }

    /**
     * @param mixed $nodeTypeId
     * @return null|NodeType
     */
    public function reverseTransform($nodeTypeId)
    {
        if (!$nodeTypeId) {
            return null;
        }

        $nodeType = $this->manager
            ->getRepository(NodeType::class)
            ->find($nodeTypeId)
        ;

        if (null === $nodeType) {
            // causes a validation error
            // this message is not shown to the user
            // see the invalid_message option
            throw new TransformationFailedException(sprintf(
                'A node-type with id "%s" does not exist!',
                $nodeTypeId
            ));
        }

        return $nodeType;
    }
}
