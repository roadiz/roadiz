<?php
declare(strict_types=1);

namespace RZ\Roadiz\Explorer;

use Psr\Container\ContainerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class AbstractExplorerProvider implements ExplorerProviderInterface
{
    protected array $options;
    private ?ContainerInterface $container = null;

    protected function getContainer(): ContainerInterface
    {
        if (null === $this->container) {
            throw new \RuntimeException('Container has not been injected in explorer');
        }
        return $this->container;
    }

    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
        return $this;
    }

    /**
     * @param $serviceName
     * @return mixed
     */
    public function get($serviceName)
    {
        return $this->getContainer()->get($serviceName);
    }

    /**
     * @param $serviceName
     * @return bool
     */
    public function has($serviceName): bool
    {
        return $this->getContainer()->has($serviceName);
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'page'       => 1,
            'search'   =>  null,
            'itemPerPage'   => 30
        ]);
    }
}
