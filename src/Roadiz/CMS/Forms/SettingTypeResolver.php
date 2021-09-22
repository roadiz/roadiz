<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Forms;

use RZ\Roadiz\Core\AbstractEntities\AbstractField;
use RZ\Roadiz\Core\Entities\Setting;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class SettingTypeResolver
{
    /**
     * Associates node-type field type to a Symfony Form type.
     */
    public array $typeToForm = [
        AbstractField::STRING_T => TextType::class,
        AbstractField::DATETIME_T => DateTimeType::class,
        AbstractField::TEXT_T => TextareaType::class,
        AbstractField::MARKDOWN_T => MarkdownType::class,
        AbstractField::BOOLEAN_T => CheckboxType::class,
        AbstractField::INTEGER_T => IntegerType::class,
        AbstractField::DECIMAL_T => NumberType::class,
        AbstractField::EMAIL_T => EmailType::class,
        AbstractField::DOCUMENTS_T => FileType::class,
        AbstractField::COLOUR_T => ColorType::class,
        AbstractField::JSON_T => JsonType::class,
        AbstractField::CSS_T => CssType::class,
        AbstractField::YAML_T => YamlType::class,
        AbstractField::ENUM_T => ChoiceType::class,
        AbstractField::MULTIPLE_T => ChoiceType::class,
    ];

    /**
     * @param Setting $setting
     * @return class-string<AbstractType>
     */
    public function getSettingType(Setting $setting): string
    {
        return $this->typeToForm[$setting->getType()] ?? TextType::class;
    }
}
