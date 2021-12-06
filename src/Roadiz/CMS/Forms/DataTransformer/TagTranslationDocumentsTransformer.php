<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Forms\DataTransformer;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ObjectManager;
use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Core\Entities\TagTranslation;
use RZ\Roadiz\Core\Entities\TagTranslationDocuments;
use RZ\Roadiz\Core\Models\DocumentInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * @package RZ\Roadiz\CMS\Forms\DataTransformer
 */
class TagTranslationDocumentsTransformer implements DataTransformerInterface
{
    private ObjectManager $manager;
    private TagTranslation $tagTranslation;

    /**
     * @param ObjectManager $manager
     * @param TagTranslation $tagTranslation
     */
    public function __construct(ObjectManager $manager, TagTranslation $tagTranslation)
    {
        $this->manager = $manager;
        $this->tagTranslation = $tagTranslation;
    }

    /**
     * Transform TagTranslationDocuments join entities
     * to Document entities for displaying in document VueJS component.
     *
     * @param TagTranslationDocuments[]|null $tagTranslationDocuments
     * @return DocumentInterface[]
     */
    public function transform($tagTranslationDocuments)
    {
        if (null === $tagTranslationDocuments || empty($tagTranslationDocuments)) {
            return [];
        }
        $documents = [];
        foreach ($tagTranslationDocuments as $tagTranslationDocument) {
            $documents[] = $tagTranslationDocument->getDocument();
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

            $ttd = new TagTranslationDocuments($this->tagTranslation, $document);
            $ttd->setPosition($position);
            $this->manager->persist($ttd);
            $documents->add($ttd);

            $position++;
        }

        return $documents;
    }
}
