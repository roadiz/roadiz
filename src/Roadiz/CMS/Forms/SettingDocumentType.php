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
 * @file SettingDocumentType.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */

namespace RZ\Roadiz\CMS\Forms;

use Doctrine\ORM\EntityManager;
use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Utils\Asset\Packages;
use RZ\Roadiz\Utils\Document\AbstractDocumentFactory;
use RZ\Roadiz\Utils\Document\DocumentFactory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SettingDocumentType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer(new CallbackTransformer(
            function ($value) use ($options) {
                /** @var Packages $packages */
                $packages = $options['assetPackages'];
                /** @var Document|null $document */
                $document = $options['entityManager']->find(Document::class, $value);
                if (null !== $document) {
                    // transform the array to a string
                    return new File($packages->getDocumentFilePath($document), false);
                }
                return null;
            },
            function ($file) use ($options) {
                if ($file instanceof UploadedFile && $file->isValid()) {
                    /** @var AbstractDocumentFactory $factory */
                    $factory = $options['documentFactory'];
                    $factory->setFile($file);
                    $document = $factory->getDocument();

                    if (null !== $document && $document instanceof Document) {
                        $options['entityManager']->persist($document);
                        $options['entityManager']->flush($document);

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
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired([
            'entityManager',
            'documentFactory',
            'assetPackages',
        ]);

        $resolver->setAllowedTypes('entityManager', [EntityManager::class]);
        $resolver->setAllowedTypes('documentFactory', [DocumentFactory::class]);
        $resolver->setAllowedTypes('assetPackages', [Packages::class]);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return FileType::class;
    }
}
