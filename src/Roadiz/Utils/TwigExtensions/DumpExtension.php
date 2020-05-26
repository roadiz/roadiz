<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\TwigExtensions;

use Doctrine\Common\Util\Debug;
use Pimple\Container;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class DumpExtension extends AbstractExtension
{
    /**
     * @var Container
     */
    private $container;

    /**
     * DumpExtension constructor.
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('dump', function (Environment $env) {
                if (!$env->isDebug()) {
                    return;
                }
                $count = func_num_args();
                for ($i = 2; $i < $count; ++$i) {
                    $var = Debug::export(func_get_arg($i), 2);
                    $this->container['messagescollector']->debug($var);
                }
            }, ['is_safe' => ['html'], 'needs_context' => true, 'needs_environment' => true]),
        ];
    }
}
