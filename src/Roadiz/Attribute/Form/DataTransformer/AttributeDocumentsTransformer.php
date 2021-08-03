<?php
declare(strict_types=1);

namespace RZ\Roadiz\Attribute\Form\DataTransformer;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ObjectManager;
use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Core\Entities\Attribute;
use RZ\Roadiz\Core\Entities\AttributeDocuments;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

final class AttributeDocumentsTransformer implements DataTransformerInterface
{
    private ObjectManager $manager;
    private Attribute $attribute;

    /**
     * @param ObjectManager $manager
     * @param Attribute $attribute
     */
    public function __construct(ObjectManager $manager, Attribute $attribute)
    {
        $this->manager = $manager;
        $this->attribute = $attribute;
    }

    /**
     * Transform AttributeDocuments join entities
     * to Document entities for displaying in document VueJS component.
     *
     * @param AttributeDocuments[]|null $attributeDocuments
     * @return Document[]
     */
    public function transform($attributeDocuments)
    {
        if (null === $attributeDocuments || empty($attributeDocuments)) {
            return [];
        }
        $documents = [];
        foreach ($attributeDocuments as $attributeDocument) {
            $documents[] = $attributeDocument->getDocument();
        }

        return $documents;
    }

    /**
     * @param array $documentIds
     * @return ArrayCollection
     */
    public function reverseTransform($documentIds)
    {
        if (!$documentIds) {
            return new ArrayCollection();
        }

        $documents = new ArrayCollection();
        $position = 0;
        foreach ($documentIds as $documentId) {
            $document = $this->manager
                ->getRepository(Document::class)
                ->find($documentId)
            ;
            if (null === $document) {
                throw new TransformationFailedException(sprintf(
                    'A document with id "%s" does not exist!',
                    $documentId
                ));
            }

            $ttd = new AttributeDocuments($this->attribute, $document);
            $ttd->setPosition($position);
            $this->manager->persist($ttd);
            $documents->add($ttd);

            $position++;
        }

        return $documents;
    }
}
