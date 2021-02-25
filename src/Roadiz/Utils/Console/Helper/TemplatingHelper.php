<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Console\Helper;

use Symfony\Component\Console\Helper\Helper;
use Twig\Environment;

class TemplatingHelper extends Helper
{
    protected Environment $templating;

    public function __construct(Environment $templating)
    {
        $this->templating = $templating;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'templating';
    }

    /**
     * Wraps Twig Environment render method.
     *
     * @param  string $templatePath
     * @param  array  $assignation
     * @return string
     */
    public function render($templatePath, $assignation = [])
    {
        return $this->templating->render($templatePath, $assignation);
    }
}
