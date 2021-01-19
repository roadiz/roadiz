<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\TwigExtensions;

use Doctrine\Common\Util\Debug;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Extension\DumpExtension as BaseDumpExtension;
use Symfony\Component\VarDumper\Cloner\ClonerInterface;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;
use Twig\Environment;

final class DumpExtension extends BaseDumpExtension
{
    /**
     * @var LoggerInterface
     */
    private LoggerInterface $messageCollector;

    public function __construct(LoggerInterface $messageCollector, ClonerInterface $cloner, HtmlDumper $dumper = null)
    {
        parent::__construct($cloner, $dumper);
        $this->messageCollector = $messageCollector;
    }

    public function dump(Environment $env, $context)
    {
        if (!$env->isDebug()) {
            return null;
        }
        $count = \func_num_args();
        for ($i = 2; $i < $count; ++$i) {
            $this->messageCollector->debug(Debug::export(\func_get_arg($i), 2));
        }

        return parent::dump(...\func_get_args());
    }
}
