<?php
/**
 * Copyright © 2019, Ambroise Maupate and Julien Blanchet
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
 * Except as contained in this notice, the name of the roadiz shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from Ambroise Maupate and Julien Blanchet.
 *
 * @file JoinDataTransformer.php
 * @author Ambroise Maupate
 *
 */
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Forms\DataTransformer;

use Doctrine\ORM\EntityManagerInterface;
use RZ\Roadiz\Core\AbstractEntities\AbstractEntity;
use RZ\Roadiz\Core\Entities\NodeTypeField;
use Symfony\Component\Form\DataTransformerInterface;

class JoinDataTransformer implements DataTransformerInterface
{
    /**
     * @var NodeTypeField
     */
    private $nodeTypeField;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var string
     */
    private $entityClassname;

    /**
     * JoinDataTransformer constructor.
     *
     * @param NodeTypeField $nodeTypeField
     * @param EntityManagerInterface $entityManager
     * @param string $entityClassname
     */
    public function __construct(
        NodeTypeField $nodeTypeField,
        EntityManagerInterface $entityManager,
        string $entityClassname
    ) {
        $this->nodeTypeField = $nodeTypeField;
        $this->entityManager = $entityManager;
        $this->entityClassname = $entityClassname;
    }

    /**
     * @param mixed $entitiesToForm
     * @return mixed
     */
    public function transform($entitiesToForm)
    {
        /*
         * If model is already an AbstractEntity
         */
        if (!empty($entitiesToForm) &&
            $entitiesToForm instanceof AbstractEntity) {
            return $entitiesToForm->getId();
        } elseif (!empty($entitiesToForm) && is_array($entitiesToForm)) {
            /*
             * If model is a collection of AbstractEntity
             */
            $idArray = [];
            foreach ($entitiesToForm as $entity) {
                if ($entity instanceof AbstractEntity) {
                    $idArray[] = $entity->getId();
                }
            }
            return $idArray;
        } elseif (!empty($entitiesToForm)) {
            return $entitiesToForm;
        }
        return '';
    }

    /**
     * @param mixed $formToEntities
     * @return mixed
     */
    public function reverseTransform($formToEntities)
    {
        if ($this->nodeTypeField->isManyToMany()) {
            return $this->entityManager->getRepository($this->entityClassname)->findBy([
                'id' => $formToEntities,
            ]);
        }
        if ($this->nodeTypeField->isManyToOne()) {
            return $this->entityManager->getRepository($this->entityClassname)->findOneBy([
                'id' => $formToEntities,
            ]);
        }
        return null;
    }
}
