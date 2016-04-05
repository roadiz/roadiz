<?php
/**
 * Copyright (c) Rezo Zero 2016.
 *
 * prison-insider
 *
 * Created on 05/04/16 11:36
 *
 * @author ambroisemaupate
 * @file NodeTypeTransformer.php
 */

namespace RZ\Roadiz\CMS\Forms\DataTransformer;

use Doctrine\Common\Persistence\ObjectManager;
use RZ\Roadiz\Core\Entities\NodeType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

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
            ->getRepository('RZ\Roadiz\Core\Entities\NodeType')
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
