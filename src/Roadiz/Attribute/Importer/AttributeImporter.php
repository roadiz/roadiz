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
 * @file AttributeImporter.php
 * @author Ambroise Maupate
 *
 */
declare(strict_types=1);

namespace RZ\Roadiz\Attribute\Importer;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityNotFoundException;
use JMS\Serializer\Serializer;
use Pimple\Container;
use RZ\Roadiz\CMS\Importers\EntityImporterInterface;
use RZ\Roadiz\Core\ContainerAwareInterface;
use RZ\Roadiz\Core\ContainerAwareTrait;
use RZ\Roadiz\Core\Entities\Attribute;
use RZ\Roadiz\Core\Entities\AttributeTranslation;

class AttributeImporter implements EntityImporterInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * AttributeImporter constructor.
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @inheritDoc
     */
    public function supports(string $entityClass): bool
    {
        return $entityClass === Attribute::class || $entityClass === 'array<' . Attribute::class . '>';
    }

    /**
     * @inheritDoc
     */
    public function import(string $serializedData): bool
    {
        /** @var EntityManager $em */
        $em = $this->get('em');
        /** @var Serializer $serializer */
        $serializer = $this->get('serializer');
        $attributes = $serializer->deserialize($serializedData, 'array<' . Attribute::class . '>', 'json');

        /** @var Attribute $attribute */
        foreach ($attributes as $attribute) {
            /** @var AttributeTranslation $attributeTranslation */
            foreach ($attribute->getAttributeTranslations() as $attributeTranslation) {
                try {
                    $em->merge($attributeTranslation);
                } catch (EntityNotFoundException $e) {
                    $attribute->setId(null);
                    $em->persist($attributeTranslation);
                }
            }
            try {
                $em->merge($attribute);
            } catch (EntityNotFoundException $e) {
                $attribute->setId(null);
                $em->persist($attribute);
            }
        }

        $em->flush();

        return true;
    }
}
