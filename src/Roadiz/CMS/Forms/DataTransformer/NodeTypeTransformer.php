<?php
/**
 * Copyright © 2016, Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * Except as contained in this notice, the name of the ROADIZ shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from Ambroise Maupate and Julien Blanchet.
 *
 * @file NodeTypeTransformer.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\CMS\Forms\DataTransformer;

use Doctrine\Common\Persistence\ObjectManager;
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
