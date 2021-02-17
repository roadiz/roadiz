<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\MediaFinders;

use Doctrine\Persistence\ObjectManager;
use GuzzleHttp\Exception\ClientException;
use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Core\Entities\DocumentTranslation;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Exceptions\APINeedsAuthentificationException;
use RZ\Roadiz\Core\Models\DocumentInterface;

trait EmbedFinderTrait
{
    /**
     * @inheritDoc
     */
    protected function documentExists(ObjectManager $objectManager, $embedId, $embedPlatform): bool
    {
        $existingDocument = $objectManager->getRepository(Document::class)
            ->findOneBy([
                'embedId' => $embedId,
                'embedPlatform' => $embedPlatform,
            ]);

        return null !== $existingDocument;
    }

    /**
     * @inheritDoc
     */
    protected function injectMetaInDocument(ObjectManager $objectManager, DocumentInterface $document)
    {
        $translations = $objectManager->getRepository(Translation::class)->findAll();

        try {
            /** @var Translation $translation */
            foreach ($translations as $translation) {
                $documentTr = new DocumentTranslation();
                $documentTr->setDocument($document);
                $documentTr->setTranslation($translation);
                $documentTr->setName($this->getMediaTitle());
                $documentTr->setDescription($this->getMediaDescription());
                $documentTr->setCopyright($this->getMediaCopyright());
                $objectManager->persist($documentTr);
            }
        } catch (APINeedsAuthentificationException $exception) {
            // do no prevent from creating document if credentials are not provided.
        } catch (ClientException $exception) {
            // do no prevent from creating document if platform has errors, such as
            // too much API usage.
        }

        return $document;
    }
}
