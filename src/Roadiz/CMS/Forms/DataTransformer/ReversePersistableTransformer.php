<?php
/**
 * Copyright (c) 2019. Ambroise Maupate and Julien Blanchet
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
 */
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Forms\DataTransformer;

use Doctrine\ORM\EntityManagerInterface;
use RZ\Roadiz\Core\AbstractEntities\PersistableInterface;
use Symfony\Component\Form\DataTransformerInterface;

/**
 * Transform Doctrine integer ID to their Doctrine entities.
 */
class ReversePersistableTransformer implements DataTransformerInterface
{
    /**
     * @var string
     */
    protected $doctrineEntity;
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * DoctrineToExplorerProviderItemTransformer constructor.
     *
     * @param EntityManagerInterface $entityManager
     * @param string                 $doctrineEntity
     */
    public function __construct(EntityManagerInterface $entityManager, string $doctrineEntity)
    {
        $this->entityManager = $entityManager;
        $this->doctrineEntity = $doctrineEntity;
    }

    public function transform($value)
    {
        if (null === $value) {
            return null;
        }
        return $this->entityManager->getRepository($this->doctrineEntity)->findBy([
            'id' => $value
        ]);
    }

    public function reverseTransform($value)
    {
        if (is_array($value)) {
            return array_map(function (PersistableInterface $item) {
                return $item->getId();
            }, $value);
        }
        if ($value instanceof PersistableInterface) {
            return $value->getId();
        }
        return null;
    }
}
