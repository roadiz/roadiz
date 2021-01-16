<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Forms;

use Doctrine\ORM\EntityManagerInterface;
use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Utils\Asset\Packages;
use RZ\Roadiz\Utils\Document\AbstractDocumentFactory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class SettingDocumentType extends AbstractType
{
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var AbstractDocumentFactory
     */
    protected $documentFactory;

    /**
     * @var Packages
     */
    protected $packages;

    /**
     * @param EntityManagerInterface $entityManager
     * @param AbstractDocumentFactory $documentFactory
     * @param Packages $packages
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        AbstractDocumentFactory $documentFactory,
        Packages $packages
    ) {
        $this->entityManager = $entityManager;
        $this->documentFactory = $documentFactory;
        $this->packages = $packages;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer(new CallbackTransformer(
            function ($value) {
                if (null !== $value) {
                    /** @var Document|null $document */
                    $document = $this->entityManager->find(Document::class, $value);
                    if (null !== $document) {
                        // transform the array to a string
                        return new File($this->packages->getDocumentFilePath($document), false);
                    }
                }
                return null;
            },
            function ($file) {
                if ($file instanceof UploadedFile && $file->isValid()) {
                    $this->documentFactory->setFile($file);
                    $document = $this->documentFactory->getDocument();

                    if (null !== $document && $document instanceof Document) {
                        $this->entityManager->persist($document);
                        $this->entityManager->flush();

                        return $document->getId();
                    }
                }
                return null;
            }
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return FileType::class;
    }
}
