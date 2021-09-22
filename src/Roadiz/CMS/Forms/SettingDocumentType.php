<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Forms;

use Doctrine\Persistence\ManagerRegistry;
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
    protected ManagerRegistry $managerRegistry;
    protected AbstractDocumentFactory $documentFactory;
    protected Packages $packages;

    /**
     * @param ManagerRegistry $managerRegistry
     * @param AbstractDocumentFactory $documentFactory
     * @param Packages $packages
     */
    public function __construct(
        ManagerRegistry $managerRegistry,
        AbstractDocumentFactory $documentFactory,
        Packages $packages
    ) {
        $this->documentFactory = $documentFactory;
        $this->packages = $packages;
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer(new CallbackTransformer(
            function ($value) {
                if (null !== $value) {
                    $manager = $this->managerRegistry->getManagerForClass(Document::class);
                    /** @var Document|null $document */
                    $document = $manager->find(Document::class, $value);
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
                        $manager = $this->managerRegistry->getManagerForClass(Document::class);
                        $manager->persist($document);
                        $manager->flush();

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
