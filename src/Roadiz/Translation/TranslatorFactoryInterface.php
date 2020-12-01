<?php
declare(strict_types=1);

namespace RZ\Roadiz\Translation;

use Symfony\Contracts\Translation\TranslatorInterface;

interface TranslatorFactoryInterface
{
    public function create(): TranslatorInterface;
}
