<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Forms\DataTransformer;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ObjectManager;
use RZ\Roadiz\Core\Entities\Document;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * Class DocumentCollectionTransformer
 * @package RZ\Roadiz\CMS\Forms\DataTransformer
 */
class DocumentCollectionTransformer implements DataTransformerInterface
{
    /**
     * @var bool
     */
    protected $asCollection;
    private $manager;

    /**
     * NodeTypeTransformer constructor.
     *
     * @param ObjectManager $manager
     * @param bool          $asCollection
     */
    public function __construct(ObjectManager $manager, bool $asCollection = false)
    {
        $this->manager = $manager;
        $this->asCollection = $asCollection;
    }

    /**
     * @param ArrayCollection<Document>|Document[]|null $documents
     * @return string|array
     */
    public function transform($documents)
    {
        if (null === $documents || empty($documents)) {
            return '';
        }
        $ids = [];
        /** @var Document $document */
        foreach ($documents as $document) {
            $ids[] = $document->getId();
        }
        if ($this->asCollection) {
            return $ids;
        }
        return implode(',', $ids);
    }

    /**
     * @param string|array|null $documentIds
     * @return array|ArrayCollection
     */
    public function reverseTransform($documentIds)
    {
        if (!$documentIds) {
            if ($this->asCollection) {
                return new ArrayCollection();
            }
            return [];
        }

        if (is_array($documentIds)) {
            $ids = $documentIds;
        } else {
            $ids = explode(',', $documentIds);
        }

        $documents = [];
        foreach ($ids as $documentId) {
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

            $documents[] = $document;
        }
        if ($this->asCollection) {
            return new ArrayCollection($documents);
        }
        return $documents;
    }
}
