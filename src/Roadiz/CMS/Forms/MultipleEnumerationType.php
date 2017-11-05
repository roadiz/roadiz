<?php
/**
 * Copyright © 2014, Ambroise Maupate and Julien Blanchet
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
 * @file MultipleEnumerationType.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\CMS\Forms;

use RZ\Roadiz\Core\Entities\NodeTypeField;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Group selector form field type.
 */
class MultipleEnumerationType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'strict' => true,
            'multiple' => true,
            'choices_as_values' => true,
        ]);

        $resolver->setRequired(['nodeTypeField']);
        $resolver->setAllowedTypes('nodeTypeField', [NodeTypeField::class]);

        $resolver->setNormalizer('placeholder', function (Options $options, $placeholder){
            if ('' !== $options['nodeTypeField']->getPlaceholder()) {
                $placeholder = $options['nodeTypeField']->getPlaceholder();
            }
            return $placeholder;
        });

        $resolver->setNormalizer('choices', function (Options $options, $choices){
            $values = explode(',', $options['nodeTypeField']->getDefaultValues());

            foreach ($values as $value) {
                $value = trim($value);
                $choices[$value] = $value;
            }
            return $choices;
        });

        $resolver->setNormalizer('expanded', function (Options $options, $expanded){
            return $options['nodeTypeField']->isExpanded();
        });
    }
    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return ChoiceType::class;
    }
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'enumeration';
    }

    /**
     * @inheritDoc
     */
    public function getBlockPrefix()
    {
        return 'enumeration';
    }
}
