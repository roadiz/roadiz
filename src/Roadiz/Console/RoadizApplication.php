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
use Doctrine\ORM\Tools\Console\ConsoleRunner;
use Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper;
use RZ\Roadiz\Core\HttpFoundation\Request;
use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Utils\Console\Helper\CacheProviderHelper;
use RZ\Roadiz\Utils\Console\Helper\ConfigurationHelper;
use RZ\Roadiz\Utils\Console\Helper\KernelHelper;
use RZ\Roadiz\Utils\Console\Helper\MailerHelper;
use RZ\Roadiz\Utils\Console\Helper\SolrHelper;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Helper\DebugFormatterHelper;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\ProcessHelper;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Helper\TableHelper;
use Symfony\Component\Console\Input\InputOption;

/**
 * Roadiz console application.
 */
class RoadizApplication extends Application
{
    protected $kernel;

    public function __construct(Kernel $kernel)
    {
        $this->kernel = $kernel;
        $this->kernel->boot();
        $this->kernel->container['request'] = Request::createFromGlobals();

        parent::__construct('Roadiz Console Application', $kernel::$cmsVersion);

        $this->getDefinition()->addOption(new InputOption('--env', '-e', InputOption::VALUE_REQUIRED, 'The Environment name.', $kernel->getEnvironment()));
        $this->getDefinition()->addOption(new InputOption('--no-debug', null, InputOption::VALUE_NONE, 'Switches off debug mode.'));
        // Use default Doctrine commands
        ConsoleRunner::addCommands($this);

        /*
         * Define a request wide timezone
         */
        if (!empty($this->kernel->container['config']["timezone"])) {
            date_default_timezone_set($this->kernel->container['config']["timezone"]);
        } else {
            date_default_timezone_set("Europe/Paris");
        }
    }

    /**
     * Gets the default commands that should always be available.
     *
     * @return Command[] An array of default Command instances
     */
    protected function getDefaultCommands()
    {
        $commands = [
            new \RZ\Roadiz\Console\TranslationsCommand(),
            new \RZ\Roadiz\Console\TranslationsCreationCommand(),
            new \RZ\Roadiz\Console\TranslationsDeleteCommand(),
            new \RZ\Roadiz\Console\TranslationsEnableCommand(),
            new \RZ\Roadiz\Console\TranslationsDisableCommand(),
            new \RZ\Roadiz\Console\NodeTypesCommand(),
            new \RZ\Roadiz\Console\NodeTypesCreationCommand(),
            new \RZ\Roadiz\Console\NodeTypesDeleteCommand(),
            new \RZ\Roadiz\Console\NodeTypesAddFieldCommand(),
            new \RZ\Roadiz\Console\NodesSourcesCommand(),
            new \RZ\Roadiz\Console\NodesCommand(),
            new \RZ\Roadiz\Console\NodesCreationCommand(),
            new \RZ\Roadiz\Console\ThemesCommand(),
            new \RZ\Roadiz\Console\InstallCommand(),
            new \RZ\Roadiz\Console\UsersCommand(),
            new \RZ\Roadiz\Console\UsersCreationCommand(),
            new \RZ\Roadiz\Console\UsersDeleteCommand(),
            new \RZ\Roadiz\Console\UsersDisableCommand(),
            new \RZ\Roadiz\Console\UsersEnableCommand(),
            new \RZ\Roadiz\Console\UsersRolesCommand(),
            new \RZ\Roadiz\Console\UsersPasswordCommand(),
            new \RZ\Roadiz\Console\RequirementsCommand(),
            new \RZ\Roadiz\Console\SolrCommand(),
            new \RZ\Roadiz\Console\SolrResetCommand(),
            new \RZ\Roadiz\Console\SolrReindexCommand(),
            new \RZ\Roadiz\Console\SolrOptimizeCommand(),
            new \RZ\Roadiz\Console\CacheCommand(),
            new \RZ\Roadiz\Console\CacheInfosCommand(),
            new \RZ\Roadiz\Console\ConfigurationCommand(),
            new \RZ\Roadiz\Console\ThemeInstallCommand(),
            new \RZ\Roadiz\Console\DocumentDownscaleCommand(),
        ];

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
        return new HelperSet([
            new FormatterHelper(),
            new DebugFormatterHelper(),
            new ProcessHelper(),
            new KernelHelper($this->kernel),
            new TableHelper(),
            'question' => new QuestionHelper(),
            'configuration' => new ConfigurationHelper($this->kernel->container['config']),
            'db' => new ConnectionHelper($this->kernel->container['em']->getConnection()),
            'em' => new EntityManagerHelper($this->kernel->container['em']),
            'solr' => new SolrHelper($this->kernel->container['solr']),
            'ns-cache' => new CacheProviderHelper($this->kernel->container['nodesSourcesUrlCacheProvider']),
            'mailer' => new MailerHelper($this->kernel->container['mailer']),
        ]);
    }
}
