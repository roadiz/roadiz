<?php
/**
 * Copyright (c) 2017. Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
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
 * @file TagTranslationDocumentsTransformer.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */
namespace RZ\Roadiz\CMS\Forms\DataTransformer;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;
use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Core\Entities\TagTranslation;
use RZ\Roadiz\Core\Entities\TagTranslationDocuments;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * Class TagTranslationDocumentsTransformer
 * @package RZ\Roadiz\CMS\Forms\DataTransformer
 */
class TagTranslationDocumentsTransformer implements DataTransformerInterface
{
    private $manager;
    /**
     * @var TagTranslation
     */
    private $tagTranslation;

    /**
     * NodeTypeTransformer constructor.
     *
     * @param ObjectManager $manager
     * @param TagTranslation $tagTranslation
     */
    public function __construct(ObjectManager $manager, TagTranslation $tagTranslation)
    {
        $this->manager = $manager;
        $this->tagTranslation = $tagTranslation;
    }

    /**
     * @param TagTranslationDocuments[] $tagTranslationDocuments
     * @return array
     */
    public function transform($tagTranslationDocuments)
    {
        if (null === $tagTranslationDocuments || empty($tagTranslationDocuments)) {
            return [];
        }
        $ids = [];
        /** @var Document $tagTranslationDocument */
        foreach ($tagTranslationDocuments as $tagTranslationDocument) {
            $ids[] = $tagTranslationDocument->getDocument()->getId();
        }

        return $ids;
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
