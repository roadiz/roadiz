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
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;

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
                'required' => false,
                'empty_data' => null,
                'label' => false,
                'constraints' => [
                    new Length([
                        'max' => 254
                    ])
                ]
            ];
            switch ($attributeValueTranslation->getAttributeValue()->getType()) {
                case AttributeInterface::INTEGER_T:
                    $builder->add('value', IntegerType::class, $defaultOptions);
                    break;
                case AttributeInterface::DECIMAL_T:
                    $builder->add('value', NumberType::class, $defaultOptions);
                    break;
                case AttributeInterface::DATE_T:
                    $builder->add('value', DateType::class, array_merge($defaultOptions, [
                        'placeholder' => [
                            'year' => 'year',
                            'month' => 'month',
                            'day' => 'day'
                        ],
                        'widget' => 'single_text',
                        'format' => 'yyyy-MM-dd',
                        'attr' => [
                            'class' => 'rz-datetime-field',
                        ],
                        'constraints' => []
                    ]));
                    break;
                case AttributeInterface::COLOUR_T:
                    $builder->add('value', TextType::class, array_merge($defaultOptions, [
                        'attr' => [
                            'class' => 'colorpicker-input',
                        ],
                    ]));

                    break;
                case AttributeInterface::COUNTRY_T:
                    $builder->add('value', CountryType::class, $defaultOptions);
                    break;
                case AttributeInterface::DATETIME_T:
                    $builder->add('value', DateTimeType::class, array_merge($defaultOptions, [
                        'placeholder' => [
                            'hour' => 'hour',
                            'minute' => 'minute',
                        ],
                        'date_widget' => 'single_text',
                        'date_format' => 'yyyy-MM-dd',
                        'attr' => [
                            'class' => 'rz-datetime-field',
                        ],
                        'constraints' => []
                    ]));
                    break;
                case AttributeInterface::BOOLEAN_T:
                    $builder->add('value', CheckboxType::class, $defaultOptions);
                    break;
                case AttributeInterface::ENUM_T:
                    $builder->add('value', ChoiceType::class, array_merge($defaultOptions, [
                        'required' => true,
                        'choices' => $this->getOptions($attributeValueTranslation)
                    ]));
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
     * @param AttributeValueTranslationInterface $attributeValueTranslation
     *
     * @return AttributeInterface|null
     */
    protected function getAttribute(AttributeValueTranslationInterface $attributeValueTranslation): ?AttributeInterface
    {
        return $attributeValueTranslation->getAttributeValue()->getAttribute();
    }

    /**
     * @param AttributeValueTranslationInterface $attributeValueTranslation
     *
     * @return array
     */
    protected function getOptions(AttributeValueTranslationInterface $attributeValueTranslation): array
    {
        $options = $this->getAttribute($attributeValueTranslation)->getOptions(
            $attributeValueTranslation->getTranslation()
        );
        if (null !== $options) {
            $options = array_combine($options, $options);
        }

        return array_merge([
            'attributes.no_value' => null,
        ], $options ?: []);
    }

    /**
     * @inheritDoc
     */
    public function getBlockPrefix()
    {
        return 'attribute_value_translation';
    }
}
