<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Events;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RZ\Roadiz\Core\Entities\Font;
use RZ\Roadiz\Utils\Asset\Packages;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Handle file management on Fonts lifecycle events.
 *
 * @package Roadiz\Core\Events
 */
final class FontLifeCycleSubscriber implements EventSubscriber
{
    private Packages $assetPackages;
    private LoggerInterface $logger;

    /**
     * @param Packages $assetPackages
     * @param LoggerInterface|null $logger
     */
    public function __construct(Packages $assetPackages, ?LoggerInterface $logger = null)
    {
        $this->assetPackages = $assetPackages;
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * {@inheritdoc}
     */
    public function getSubscribedEvents()
    {
        return [
            Events::prePersist,
            Events::preUpdate,
            Events::preRemove,
            Events::postPersist,
            Events::postUpdate,
        ];
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function prePersist(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();
        // perhaps you only want to act on some "Font" entity
        if ($entity instanceof Font) {
            $this->setFontFilesNames($entity);
        }
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function preUpdate(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();
        // perhaps you only want to act on some "Font" entity
        if ($entity instanceof Font) {
            $this->setFontFilesNames($entity);
        }
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function postPersist(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();
        // perhaps you only want to act on some "Font" entity
        if ($entity instanceof Font) {
            $this->upload($entity);
        }
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function postUpdate(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();
        // perhaps you only want to act on some "Font" entity
        if ($entity instanceof Font) {
            $this->upload($entity);
        }
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function preRemove(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();
        // perhaps you only want to act on some "Product" entity
        if ($entity instanceof Font) {
            $fileSystem = new Filesystem();
            try {
                if (null !== $entity->getSVGFilename()) {
                    $svgFilePath = $this->assetPackages->getFontsPath($entity->getSVGRelativeUrl());
                    $this->logger->info('Font file deleted', ['file' => $svgFilePath]);
                    $fileSystem->remove($svgFilePath);
                }
                if (null !== $entity->getOTFFilename()) {
                    $otfFilePath = $this->assetPackages->getFontsPath($entity->getOTFRelativeUrl());
                    $this->logger->info('Font file deleted', ['file' => $otfFilePath]);
                    $fileSystem->remove($otfFilePath);
                }
                if (null !== $entity->getEOTFilename()) {
                    $eotFilePath = $this->assetPackages->getFontsPath($entity->getEOTRelativeUrl());
                    $this->logger->info('Font file deleted', ['file' => $eotFilePath]);
                    $fileSystem->remove($eotFilePath);
                }
                if (null !== $entity->getWOFFFilename()) {
                    $woffFilePath = $this->assetPackages->getFontsPath($entity->getWOFFRelativeUrl());
                    $this->logger->info('Font file deleted', ['file' => $woffFilePath]);
                    $fileSystem->remove($woffFilePath);
                }
                if (null !== $entity->getWOFF2Filename()) {
                    $woff2FilePath = $this->assetPackages->getFontsPath($entity->getWOFF2RelativeUrl());
                    $this->logger->info('Font file deleted', ['file' => $woff2FilePath]);
                    $fileSystem->remove($woff2FilePath);
                }

                /*
                 * Removing font folder if empty.
                 */
                $fontFolderPath = $this->assetPackages->getFontsPath($entity->getFolder());
                if ($fileSystem->exists($fontFolderPath)) {
                    $isDirEmpty = !(new \FilesystemIterator($fontFolderPath))->valid();
                    if ($isDirEmpty) {
                        $this->logger->info('Font folder is empty, deleting…', ['folder' => $fontFolderPath]);
                        $fileSystem->remove($fontFolderPath);
                    }
                }
            } catch (IOException $e) {
                //do nothing
            }
        }
    }

    /**
     * @param Font $font
     */
    public function setFontFilesNames(Font $font)
    {
        if ($font->getHash() == "") {
            $font->generateHashWithSecret('default_roadiz_secret');
        }

        if (null !== $font->getSvgFile()) {
            $font->setSVGFilename($font->getSvgFile()->getClientOriginalName());
        }
        if (null !== $font->getOtfFile()) {
            $font->setOTFFilename($font->getOtfFile()->getClientOriginalName());
        }
        if (null !== $font->getEotFile()) {
            $font->setEOTFilename($font->getEotFile()->getClientOriginalName());
        }
        if (null !== $font->getWoffFile()) {
            $font->setWOFFFilename($font->getWoffFile()->getClientOriginalName());
        }
        if (null !== $font->getWoff2File()) {
            $font->setWOFF2Filename($font->getWoff2File()->getClientOriginalName());
        }
    }

    /**
     * @param Font $font
     */
    public function upload(Font $font)
    {
        $fontFolderPath = $this->assetPackages->getFontsPath($font->getFolder());

        if (null !== $font->getSvgFile()) {
            $font->getSvgFile()->move($fontFolderPath, $font->getSVGFilename());
            $font->setSvgFile(null);
        }
        if (null !== $font->getOtfFile()) {
            $font->getOtfFile()->move($fontFolderPath, $font->getOTFFilename());
            $font->setOtfFile(null);
        }
        if (null !== $font->getEotFile()) {
            $font->getEotFile()->move($fontFolderPath, $font->getEOTFilename());
            $font->setEotFile(null);
        }
        if (null !== $font->getWoffFile()) {
            $font->getWoffFile()->move($fontFolderPath, $font->getWOFFFilename());
            $font->setWoffFile(null);
        }
        if (null !== $font->getWoff2File()) {
            $font->getWoff2File()->move($fontFolderPath, $font->getWOFF2Filename());
            $font->setWoff2File(null);
        }
    }
}
