<?php
declare(strict_types=1);

namespace Themes\Rozier\Explorer;

use RZ\Roadiz\Core\ContainerAwareTrait;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class AbstractExplorerProvider implements ExplorerProviderInterface
{
    use ContainerAwareTrait;

    /**
     * @var array
     */
    protected $options;

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
