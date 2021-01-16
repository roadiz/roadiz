<?php
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Forms\Constraints;

use Symfony\Component\Validator\Constraint;

class UniqueNodeName extends Constraint
{
    public $currentValue = null;
    public $message = 'nodeName.alreadyExists';
    public $messageUrlAlias = 'nodeName.alreadyExists.as.urlAlias';
}
