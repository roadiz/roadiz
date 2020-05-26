<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Forms\Constraints;

use Symfony\Component\Validator\Constraint;

class UniqueCustomFormFieldName extends Constraint
{
    public $entityManager = null;
    public $customForm = null;
    public $currentValue = null;

    public $message = 'customFormField.name.alreadyExists';
}
