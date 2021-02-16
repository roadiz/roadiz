<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Console\Helper;

use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Translation\TranslatorInterface;

class TranslatorHelper extends Helper
{
    protected TranslatorInterface $translator;

    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'translator';
    }

    /**
     * Translates the given message.
     *
     * Wraps the Translator trans method.
     *
     * @param  string $id
     * @param  array  $parameters
     * @param  null   $domain
     * @param  null   $locale
     *
     * @return string
     */
    public function trans($id, array $parameters = [], $domain = null, $locale = null)
    {
        return $this->translator->trans($id, $parameters, $domain, $locale);
    }


    /**
     * Translates the given message.
     *
     * Wraps the Translator transChoice method.
     *
     * @param  string $id
     * @param  int    $number
     * @param  array  $parameters
     * @param  null   $domain
     * @param  null   $locale
     *
     * @return string
     */
    public function transChoice($id, $number, array $parameters = [], $domain = null, $locale = null)
    {
        return $this->translator->transChoice($id, $number, $parameters, $domain, $locale);
    }
}
