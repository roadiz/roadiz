<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Forms\Constraints;

use RZ\Roadiz\Config\CollectionFieldConfiguration;
use RZ\Roadiz\Config\JoinNodeTypeFieldConfiguration;
use RZ\Roadiz\Config\ProviderFieldConfiguration;
use RZ\Roadiz\Core\AbstractEntities\AbstractEntity;
use RZ\Roadiz\Core\Entities\NodeTypeField as NodeTypeFieldEntity;
use RZ\Roadiz\Explorer\AbstractExplorerProvider;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

class NodeTypeFieldValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if ($value instanceof NodeTypeFieldEntity) {
            if ($value->isMarkdown()) {
                $this->validateMarkdownOptions($value);
            }
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
     * @param NodeTypeFieldEntity $value
     * @param Constraint $constraint
     */
    protected function validateJoinTypes(NodeTypeFieldEntity $value, Constraint $constraint)
    {
        try {
            $defaultValuesParsed = Yaml::parse($value->getDefaultValues() ?? '');
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
     * @param NodeTypeFieldEntity $value
     * @param Constraint $constraint
     *
     * @throws \ReflectionException
     */
    protected function validateProviderTypes(NodeTypeFieldEntity $value, Constraint $constraint)
    {
        try {
            if (null === $value->getDefaultValues()) {
                $this->context->buildViolation('default_values_should_not_be_empty_for_this_type')->atPath('defaultValues')->addViolation();
            } else {
                $defaultValuesParsed = Yaml::parse($value->getDefaultValues() ?? '');
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
            }
        } catch (ParseException $e) {
            $this->context->buildViolation($e->getMessage())->atPath('defaultValues')->addViolation();
        } catch (\RuntimeException $e) {
            $this->context->buildViolation($e->getMessage())->atPath('defaultValues')->addViolation();
        }
    }

    /**
     * @param NodeTypeFieldEntity $value
     * @param Constraint $constraint
     */
    protected function validateCollectionTypes(NodeTypeFieldEntity $value, Constraint $constraint)
    {
        try {
            $defaultValuesParsed = Yaml::parse($value->getDefaultValues() ?? '');
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

    /**
     * @param NodeTypeFieldEntity $value
     */
    protected function validateMarkdownOptions(NodeTypeFieldEntity $value)
    {
        try {
            $options = Yaml::parse($value->getDefaultValues() ?? '');
            if (null !== $options && !is_array($options)) {
                $this->context
                    ->buildViolation('Markdown options must be an array.')
                    ->atPath('defaultValues')
                    ->addViolation();
            }
        } catch (ParseException $e) {
            $this->context->buildViolation($e->getMessage())->atPath('defaultValues')->addViolation();
        }
    }
}
