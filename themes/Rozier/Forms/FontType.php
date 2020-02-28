<?php
/**
 * Copyright © 2015, Ambroise Maupate and Julien Blanchet
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
 * Except as contained in this notice, the name of the ROADIZ shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from Ambroise Maupate and Julien Blanchet.
 *
 * @file FontType.php
 * @author Ambroise Maupate
 */
namespace Themes\Rozier\Forms;

use Doctrine\Persistence\ObjectManager;
use RZ\Roadiz\CMS\Forms\FontVariantsType;
use RZ\Roadiz\Core\Entities\Font;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * FontType.
 */
class FontType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name', TextType::class, [
                'label' => 'font.name',
                'help' => 'font_name_should_be_the_same_for_all_variants',
                'constraints' => [
                    new NotBlank(),
                    new Length([
                        'max' => 100,
                    ])
                ],
            ])
            ->add('hash', TextType::class, [
                'label' => 'font.cssfamily',
                'empty_data' => '',
                'help' => 'css_font_family_hash_is_automatically_generated_from_font_name',
            ])
            ->add('variant', FontVariantsType::class, [
                'label' => 'font.variant',
            ])
            ->add('eotFile', FileType::class, [
                'label' => 'font.eotFile',
                'required' => false,
                'data_class' => UploadedFile::class,
                'constraints' => [
                    new File([
                        'mimeTypes' => [
                            Font::MIME_EOT,
                            Font::MIME_DEFAULT,
                        ],
                        'mimeTypesMessage' => 'file.is_not_a.valid.font.file',
                    ]),
                ],
            ])
            ->add('svgFile', FileType::class, [
                'label' => 'font.svgFile',
                'required' => false,
                'multiple' => false,
                'constraints' => [
                    new File([
                        'mimeTypes' => [
                            Font::MIME_SVG,
                        ],
                        'mimeTypesMessage' => 'file.is_not_a.valid.font.file',
                    ]),
                ],
            ])
            ->add('otfFile', FileType::class, [
                'label' => 'font.otfFile',
                'required' => false,
                'multiple' => false,
                'constraints' => [
                    new File([
                        'mimeTypes' => [
                            Font::MIME_OTF,
                            Font::MIME_TTF,
                            'application/font-otf',
                            'application/x-font-otf',
                            'application/font-ttf',
                            'application/x-font-ttf',
                            'application/vnd.ms-opentype',
                            'application/font-sfnt',
                            'font/sfnt',
                            Font::MIME_DEFAULT,
                        ],
                        'mimeTypesMessage' => 'file.is_not_a.valid.font.file',
                    ]),
                ],
            ])
            ->add('woffFile', FileType::class, [
                'label' => 'font.woffFile',
                'required' => false,
                'multiple' => false,
                'constraints' => [
                    new File([
                        'mimeTypes' => [
                            Font::MIME_WOFF,
                            'application/x-font-woff',
                            Font::MIME_DEFAULT,
                        ],
                        'mimeTypesMessage' => 'file.is_not_a.valid.font.file',
                    ]),
                ],
            ])
            ->add('woff2File', FileType::class, [
                'label' => 'font.woff2File',
                'required' => false,
                'multiple' => false,
                'constraints' => [
                    new File([
                        'mimeTypes' => [
                            Font::MIME_WOFF2,
                            Font::MIME_DEFAULT,
                        ],
                        'mimeTypesMessage' => 'file.is_not_a.valid.font.file',
                    ]),
                ],
            ]);
    }

    public function getBlockPrefix()
    {
        return 'font';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'label' => false,
            'name' => '',
            'variant' => Font::REGULAR,
            'data_class' => Font::class,
            'attr' => [
                'class' => 'uk-form font-form',
            ],
        ]);

        $resolver->setRequired([
            'em',
        ]);

        $resolver->setAllowedTypes('em', ObjectManager::class);
        $resolver->setAllowedTypes('name', 'string');
        $resolver->setAllowedTypes('variant', 'integer');
    }
}
