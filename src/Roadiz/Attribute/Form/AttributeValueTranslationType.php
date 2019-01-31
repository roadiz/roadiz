<?php
/**
 * Copyright © 2019, Ambroise Maupate and Julien Blanchet
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
 * Except as contained in this notice, the name of the roadiz shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from Ambroise Maupate and Julien Blanchet.
 *
 * @file AttributeValueTranslationType.php
 * @author Ambroise Maupate
 *
 */
declare(strict_types=1);

namespace RZ\Roadiz\Attribute\Form;

use RZ\Roadiz\Attribute\Model\AttributeInterface;
use RZ\Roadiz\Attribute\Model\AttributeValueTranslationInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Email;

class AttributeValueTranslationType extends AbstractType
{
    /**
     * @inheritDoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $attributeValueTranslation = $builder->getData();

        if ($attributeValueTranslation instanceof AttributeValueTranslationInterface) {
            $defaultOptions = [
                'label' => $attributeValueTranslation->getAttributeValue()
                    ->getAttribute()
                    ->getLabelOrCode($attributeValueTranslation->getTranslation())
            ];
            switch ($attributeValueTranslation->getAttributeValue()->getType()) {
                case AttributeInterface::INTEGER_T:
                    $builder->add('value', IntegerType::class, $defaultOptions);
                    break;
                case AttributeInterface::DECIMAL_T:
                    $builder->add('value', IntegerType::class, $defaultOptions);
                    break;
                case AttributeInterface::EMAIL_T:
                    $builder->add('value', EmailType::class, array_merge($defaultOptions, [
                        'constraints' => [
                            new Email()
                        ]
                    ]));
                    break;
                default:
                    $builder->add('value', TextType::class, $defaultOptions);
                    break;
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function getBlockPrefix()
    {
        return 'attribute_value_translation';
    }
}
