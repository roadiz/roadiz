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
 * @file NodeTypeFieldValidator.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */

namespace RZ\Roadiz\CMS\Forms\Constraints;

use RZ\Roadiz\Config\CollectionFieldConfiguration;
use RZ\Roadiz\Config\JoinNodeTypeFieldConfiguration;
use RZ\Roadiz\Config\ProviderFieldConfiguration;
use RZ\Roadiz\Core\AbstractEntities\AbstractEntity;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;
use Themes\Rozier\Explorer\AbstractExplorerProvider;

class NodeTypeFieldValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if ($value instanceof \RZ\Roadiz\Core\Entities\NodeTypeField) {
            if ($value->isManyToMany() || $value->isManyToOne()) {
                $this->validateJoinTypes($value, $constraint);
            }
            if ($value->isMultiProvider() || $value->isSingleProvider()) {
                $this->validateProviderTypes($value, $constraint);
            }
            if ($value->isCollection()) {
                $this->validateCollectionTypes($value, $constraint);
            }
        } else {
            $this->context->buildViolation('Value is not a valid NodeTypeField.')->addViolation();
        }
    }

    /**
     * @param \RZ\Roadiz\Core\Entities\NodeTypeField $value
     * @param Constraint $constraint
     */
    protected function validateJoinTypes(\RZ\Roadiz\Core\Entities\NodeTypeField $value, Constraint $constraint)
    {
        try {
            $defaultValuesParsed = Yaml::parse($value->getDefaultValues());
            if (null === $defaultValuesParsed) {
                $this->context->buildViolation('default_values_should_not_be_empty_for_this_type')->atPath('defaultValues')->addViolation();
            } elseif (!is_array($defaultValuesParsed)) {
                $this->context->buildViolation('default_values_should_be_a_yaml_configuration_for_this_type')->atPath('defaultValues')->addViolation();
            } else {
                $configs = [
                    $defaultValuesParsed,
                ];
                $processor = new Processor();
                $joinConfig = new JoinNodeTypeFieldConfiguration();
                $configuration = $processor->processConfiguration($joinConfig, $configs);

                if (!class_exists($configuration['classname'])) {
                    $this->context->buildViolation('classname_%classname%_does_not_exist')
                        ->setParameter('%classname%', $configuration['classname'])
                        ->atPath('defaultValues')
                        ->addViolation();
                    return;
                }

                $reflection = new \ReflectionClass($configuration['classname']);
                if (!$reflection->isSubclassOf(AbstractEntity::class)) {
                    $this->context->buildViolation('classname_%classname%_must_extend_abstract_entity_class')
                        ->setParameter('%classname%', $configuration['classname'])
                        ->atPath('defaultValues')
                        ->addViolation();
                }

                if (!$reflection->hasMethod($configuration['displayable'])) {
                    $this->context->buildViolation('classname_%classname%_does_not_declare_%method%_method')
                        ->setParameter('%classname%', $configuration['classname'])
                        ->setParameter('%method%', $configuration['displayable'])
                        ->atPath('defaultValues')
                        ->addViolation();
                }

                if (!empty($configuration['alt_displayable'])) {
                    if (!$reflection->hasMethod($configuration['alt_displayable'])) {
                        $this->context->buildViolation('classname_%classname%_does_not_declare_%method%_method')
                            ->setParameter('%classname%', $configuration['classname'])
                            ->setParameter('%method%', $configuration['alt_displayable'])
                            ->atPath('defaultValues')
                            ->addViolation();
                    }
                }
            }
        } catch (ParseException $e) {
            $this->context->buildViolation($e->getMessage())->atPath('defaultValues')->addViolation();
        } catch (\RuntimeException $e) {
            $this->context->buildViolation($e->getMessage())->atPath('defaultValues')->addViolation();
        }
    }

    /**
     * @param \RZ\Roadiz\Core\Entities\NodeTypeField $value
     * @param Constraint $constraint
     */
    protected function validateProviderTypes(\RZ\Roadiz\Core\Entities\NodeTypeField $value, Constraint $constraint)
    {
        try {
            $defaultValuesParsed = Yaml::parse($value->getDefaultValues());
            if (null === $defaultValuesParsed) {
                $this->context->buildViolation('default_values_should_not_be_empty_for_this_type')->atPath('defaultValues')->addViolation();
            } elseif (!is_array($defaultValuesParsed)) {
                $this->context->buildViolation('default_values_should_be_a_yaml_configuration_for_this_type')->atPath('defaultValues')->addViolation();
            } else {
                $configs = [
                    $defaultValuesParsed,
                ];
                $processor = new Processor();
                $providerConfig = new ProviderFieldConfiguration();
                $configuration = $processor->processConfiguration($providerConfig, $configs);

                if (!class_exists($configuration['classname'])) {
                    $this->context->buildViolation('classname_%classname%_does_not_exist')
                        ->setParameter('%classname%', $configuration['classname'])
                        ->atPath('defaultValues')
                        ->addViolation();
                    return;
                }

                $reflection = new \ReflectionClass($configuration['classname']);
                if (!$reflection->isSubclassOf(AbstractExplorerProvider::class)) {
                    $this->context->buildViolation('classname_%classname%_must_extend_abstract_explorer_provider_class')
                        ->setParameter('%classname%', $configuration['classname'])
                        ->atPath('defaultValues')
                        ->addViolation();
                }
            }
        } catch (ParseException $e) {
            $this->context->buildViolation($e->getMessage())->atPath('defaultValues')->addViolation();
        } catch (\RuntimeException $e) {
            $this->context->buildViolation($e->getMessage())->atPath('defaultValues')->addViolation();
        }
    }

    /**
     * @param \RZ\Roadiz\Core\Entities\NodeTypeField $value
     * @param Constraint $constraint
     */
    protected function validateCollectionTypes(\RZ\Roadiz\Core\Entities\NodeTypeField $value, Constraint $constraint)
    {
        try {
            $defaultValuesParsed = Yaml::parse($value->getDefaultValues());
            if (null === $defaultValuesParsed) {
                $this->context->buildViolation('default_values_should_not_be_empty_for_this_type')->atPath('defaultValues')->addViolation();
            } elseif (!is_array($defaultValuesParsed)) {
                $this->context->buildViolation('default_values_should_be_a_yaml_configuration_for_this_type')->atPath('defaultValues')->addViolation();
            } else {
                $configs = [
                    $defaultValuesParsed,
                ];
                $processor = new Processor();
                $providerConfig = new CollectionFieldConfiguration();
                $configuration = $processor->processConfiguration($providerConfig, $configs);

                if (!class_exists($configuration['entry_type'])) {
                    $this->context->buildViolation('classname_%classname%_does_not_exist')
                        ->setParameter('%classname%', $configuration['entry_type'])
                        ->atPath('defaultValues')
                        ->addViolation();
                    return;
                }

                $reflection = new \ReflectionClass($configuration['entry_type']);
                if (!$reflection->isSubclassOf(AbstractType::class)) {
                    $this->context->buildViolation('classname_%classname%_must_extend_abstract_type_class')
                        ->setParameter('%classname%', $configuration['entry_type'])
                        ->atPath('defaultValues')
                        ->addViolation();
                }
            }
        } catch (ParseException $e) {
            $this->context->buildViolation($e->getMessage())->atPath('defaultValues')->addViolation();
        } catch (\RuntimeException $e) {
            $this->context->buildViolation($e->getMessage())->atPath('defaultValues')->addViolation();
        }
    }
}
