<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\TwigExtensions;

use RZ\Roadiz\Core\Authorization\Chroot\NodeChrootResolver;
use RZ\Roadiz\Core\Bags\Settings;
use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Preview\PreviewResolverInterface;
use Symfony\Bridge\Twig\AppVariable;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class RoadizExtension extends AbstractExtension implements GlobalsInterface
{
    protected Kernel $kernel;

    /**
     * @param Kernel $kernel
     */
    public function __construct(Kernel $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * @return array
     */
    public function getGlobals(): array
    {
        $appVariable = new AppVariable();
        $appVariable->setDebug($this->kernel->isDebug());
        $appVariable->setEnvironment($this->kernel->getEnvironment());
        $appVariable->setRequestStack($this->kernel->get('requestStack'));
        $appVariable->setTokenStorage($this->kernel->get('securityTokenStorage'));

        /** @var Settings $settingsBag */
        $settingsBag = $this->kernel->get('settingsBag');
        return [
            'cms_version' => !$settingsBag->get('hide_roadiz_version', false) ? Kernel::$cmsVersion : null,
            'cms_prefix' => !$settingsBag->get('hide_roadiz_version', false) ? Kernel::CMS_VERSION : null,
            'help_external_url' => 'http://docs.roadiz.io',
            'request' => $this->kernel->get('requestStack')->getCurrentRequest(),
            'is_debug' => $this->kernel->isDebug(),
            'is_preview' => $this->kernel->get(PreviewResolverInterface::class)->isPreview(),
            'is_dev_mode' => $this->kernel->isDevMode(),
            'is_prod_mode' => $this->kernel->isProdMode(),
            'bags' => [
                'settings' => $settingsBag,
                'roles' => $this->kernel->get('rolesBag'),
                'nodeTypes' => $this->kernel->get('nodeTypesBag'),
            ],
            'app' => $appVariable,
            'chroot_resolver' => $this->kernel->get(NodeChrootResolver::class),
            'meta' => [
                'siteName' => $settingsBag->get('site_name'),
                'siteCopyright' => $settingsBag->get('site_copyright'),
                'siteDescription' => $settingsBag->get('seo_description'),
            ]
        ];
    }
}
