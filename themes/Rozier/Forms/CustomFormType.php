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
 * @file CustomFormType.php
 * @author Ambroise Maupate
 */
namespace Themes\Rozier\Forms;

use RZ\Roadiz\CMS\Forms\Constraints\HexadecimalColor;
use RZ\Roadiz\CMS\Forms\Constraints\UniqueCustomFormName;
use RZ\Roadiz\CMS\Forms\MarkdownType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 *
 */
class CustomFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('displayName', 'text', [
                'label' => 'customForm.displayName',
                'constraints' => [
                    new NotBlank(),
                    new UniqueCustomFormName([
                        'entityManager' => $options['em'],
                        'currentValue' => $options['name'],
                    ]),
                ],
            ])
            ->add('description', new MarkdownType(), [
                'label' => 'description',
                'required' => false,
            ])
            ->add('email', 'email', [
                'label' => 'email',
                'required' => false,
                'constraints' => [
                    new Email(),
                ],
            ])
            ->add('open', 'checkbox', [
                'label' => 'customForm.open',
                'required' => false,
            ])
            ->add('closeDate', 'datetime', [
                'label' => 'customForm.closeDate',
                'required' => true,
                'date_widget' => 'single_text',
                'date_format' => 'yyyy-MM-dd',
                'attr' => [
                    'class' => 'rz-datetime-field',
                ],
                'empty_value' => [
                    'hour' => 'hour',
                    'minute' => 'minute',
                ],
            ])
            ->add('color', 'text', [
                'label' => 'customForm.color',
                'required' => false,
                'attr' => ['class' => 'colorpicker-input'],
                'constraints' => [
                    new HexadecimalColor(),
                ],
            ]);
    }

    public function getName()
    {
        return 'customform';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'label' => false,
            'name' => '',
            'data_class' => 'RZ\Roadiz\Core\Entities\CustomForm',
            'attr' => [
                'class' => 'uk-form custom-form-form',
            ],
        ]);

        $resolver->setRequired([
            'em',
        ]);

        $resolver->setAllowedTypes([
            'em' => 'Doctrine\Common\Persistence\ObjectManager',
            'name' => 'string',
        ]);
    }
}
