<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Forms\Constraints;

use Symfony\Component\Validator\Constraint;

class ValidYaml extends Constraint
{
    public $message = 'yaml.is.not.valid.{{ error }}';
}
