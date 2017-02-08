<?php
/**
 * Copyright © 2015, Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * Except as contained in this notice, the name of the ROADIZ shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from Ambroise Maupate and Julien Blanchet.
 *
 * @file RoadizApplication.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Console;

use Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper;
use Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper;
use RZ\Roadiz\Core\HttpFoundation\Request;
use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Utils\Console\Helper\AssetPackagesHelper;
use RZ\Roadiz\Utils\Console\Helper\CacheProviderHelper;
use RZ\Roadiz\Utils\Console\Helper\ConfigurationHelper;
use RZ\Roadiz\Utils\Console\Helper\KernelHelper;
use RZ\Roadiz\Utils\Console\Helper\LoggerHelper;
use RZ\Roadiz\Utils\Console\Helper\MailerHelper;
use RZ\Roadiz\Utils\Console\Helper\SolrHelper;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\InputOption;

/**
 * Roadiz console application.
 */
class RoadizApplication extends Application
{
    /**
     * @var Kernel
     */
    protected $kernel;

    /**
     * RoadizApplication constructor.
     * @param Kernel $kernel
     */
    public function __construct(Kernel $kernel)
    {
        $this->kernel = $kernel;
        $this->kernel->boot();
        $this->kernel->container['request'] = Request::createFromGlobals();
        $this->kernel->container['requestStack']->push($this->kernel->container['request']);

        parent::__construct('Roadiz Console Application', $kernel::$cmsVersion);

        $this->getDefinition()->addOption(new InputOption('--env', '-e', InputOption::VALUE_REQUIRED, 'The Environment name.', $kernel->getEnvironment()));
        $this->getDefinition()->addOption(new InputOption('--preview', null, InputOption::VALUE_NONE, 'Preview mode.'));
        $this->getDefinition()->addOption(new InputOption('--no-debug', null, InputOption::VALUE_NONE, 'Switches off debug mode.'));

        $this->addDoctrineCommands();

        /*
         * Define a request wide timezone
         */
        if (!empty($this->kernel->container['config']["timezone"])) {
            date_default_timezone_set($this->kernel->container['config']["timezone"]);
        } else {
            date_default_timezone_set("Europe/Paris");
        }
    }

    protected function addDoctrineCommands()
    {
        $this->addCommands(array(
            new \Doctrine\ORM\Tools\Console\Command\SchemaTool\CreateCommand(),
            new \Doctrine\ORM\Tools\Console\Command\SchemaTool\UpdateCommand(),
            new \Doctrine\ORM\Tools\Console\Command\ValidateSchemaCommand(),
            new \Doctrine\ORM\Tools\Console\Command\InfoCommand(),
        ));
    }

    /**
     * Gets the default commands that should always be available.
     * @return Command[] An array of default Command instances
     * @throws \Exception
     */
    protected function getDefaultCommands()
    {
        $commands = array(
            new DispatcherDebugCommand(),
            new TranslationsCommand(),
            new TranslationsCreationCommand(),
            new TranslationsDeleteCommand(),
            new TranslationsEnableCommand(),
            new TranslationsDisableCommand(),
            new NodeTypesCommand(),
            new NodeTypesCreationCommand(),
            new NodeTypesDeleteCommand(),
            new NodeTypesAddFieldCommand(),
            new NodesSourcesCommand(),
            new NodesCommand(),
            new NodesCreationCommand(),
            new NodesDetailsCommand(),
            new NodesCleanNamesCommand(),
            new NodeApplyUniversalFieldsCommand(),
            new ThemesCommand(),
            new InstallCommand(),
            new UsersCommand(),
            new UsersCreationCommand(),
            new UsersDeleteCommand(),
            new UsersDisableCommand(),
            new UsersEnableCommand(),
            new UsersRolesCommand(),
            new UsersPasswordCommand(),
            new RequirementsCommand(),
            new SolrCommand(),
            new SolrResetCommand(),
            new SolrReindexCommand(),
            new SolrOptimizeCommand(),
            new CacheCommand(),
            new CacheInfosCommand(),
            new ConfigurationCommand(),
            new ThemeInstallCommand(),
            new DocumentDownscaleCommand(),
            new NodesOrphansCommand(),
            new DatabaseDumpCommand(),
            new FilesExportCommand(),
            new FilesImportCommand(),
        );

        /*
         * Register user defined Commands
         * Add them in your config.yml
         */
        if (isset($this->kernel->container['config']['additionalCommands'])) {
            foreach ($this->kernel->container['config']['additionalCommands'] as $commandClass) {
                if (class_exists($commandClass)) {
                    $commands[] = new $commandClass();
                } else {
                    throw new \Exception("Command class does not exists (" . $commandClass . ")", 1);
                }
            }
        }

        return array_merge(parent::getDefaultCommands(), $commands);
    }

    /**
     * Gets the default helper set with the helpers that should always be available.
     *
     * @return HelperSet A HelperSet instance
     */
    protected function getDefaultHelperSet()
    {
        $helperSet = parent::getDefaultHelperSet();

        $helperSet->set(new KernelHelper($this->kernel));
        $helperSet->set(new LoggerHelper($this->kernel));
        $helperSet->set(new AssetPackagesHelper($this->kernel->container['assetPackages']));
        $helperSet->set(new ConfigurationHelper($this->kernel->container['config']));
        $helperSet->set(new ConnectionHelper($this->kernel->container['em']->getConnection()));
        // We need to set «em» alias as Doctrine misnamed its Helper :-(
        $helperSet->set(new EntityManagerHelper($this->kernel->container['em']), 'em');
        $helperSet->set(new SolrHelper($this->kernel->container['solr']));
        $helperSet->set(new CacheProviderHelper($this->kernel->container['nodesSourcesUrlCacheProvider']));
        $helperSet->set(new MailerHelper($this->kernel->container['mailer']));

        return $helperSet;
    }
}
