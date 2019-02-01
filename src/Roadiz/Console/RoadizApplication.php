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

use Doctrine\DBAL\Exception\ConnectionException;
use Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Console\Command\InfoCommand;
use Doctrine\ORM\Tools\Console\Command\SchemaTool\CreateCommand;
use Doctrine\ORM\Tools\Console\Command\SchemaTool\DropCommand;
use Doctrine\ORM\Tools\Console\Command\SchemaTool\UpdateCommand;
use Doctrine\ORM\Tools\Console\Command\ValidateSchemaCommand;
use Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper;
use RZ\Roadiz\Core\Exceptions\NoConfigurationFoundException;
use RZ\Roadiz\Core\HttpFoundation\Request;
use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Utils\Console\Helper\AssetPackagesHelper;
use RZ\Roadiz\Utils\Console\Helper\CacheProviderHelper;
use RZ\Roadiz\Utils\Console\Helper\ConfigurationHandlerHelper;
use RZ\Roadiz\Utils\Console\Helper\ConfigurationHelper;
use RZ\Roadiz\Utils\Console\Helper\HandlerFactoryHelper;
use RZ\Roadiz\Utils\Console\Helper\KernelHelper;
use RZ\Roadiz\Utils\Console\Helper\LoggerHelper;
use RZ\Roadiz\Utils\Console\Helper\MailerHelper;
use RZ\Roadiz\Utils\Console\Helper\RolesBagHelper;
use RZ\Roadiz\Utils\Console\Helper\SolrHelper;
use RZ\Roadiz\Utils\Console\Helper\ThemeResolverHelper;
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

        if (!$this->kernel->has('request') ||
            null === $this->kernel->get('request')) {
            $this->kernel->getContainer()->offsetSet('request', Request::createFromGlobals());
            $this->kernel->get('requestStack')->push($this->kernel->get('request'));
        }

        parent::__construct('Roadiz Console Application', $kernel::$cmsVersion);

        /*
         * Use the same dispatcher as Kernel
         * to dispatch ThemeResolver event
         */
        $dispatcher = $this->kernel->get('dispatcher');
        $this->setDispatcher($dispatcher);

        $this->getDefinition()->addOption(new InputOption(
            '--env',
            '-e',
            InputOption::VALUE_REQUIRED,
            'The Environment name.',
            $kernel->getEnvironment()
        ));
        $this->getDefinition()->addOption(new InputOption(
            '--preview',
            null,
            InputOption::VALUE_NONE,
            'Preview mode.'
        ));
        $this->getDefinition()->addOption(new InputOption(
            '--no-debug',
            null,
            InputOption::VALUE_NONE,
            'Switches off debug mode.'
        ));

        $this->addDoctrineCommands();

        try {
            /*
             * Define a request wide timezone
             */
            if (!empty($this->kernel->get('config')["timezone"])) {
                date_default_timezone_set($this->kernel->get('config')["timezone"]);
            } else {
                date_default_timezone_set("Europe/Paris");
            }
        } catch (NoConfigurationFoundException $e) {
            date_default_timezone_set("Europe/Paris");
        }
    }

    protected function addDoctrineCommands()
    {
        $this->addCommands([
            new CreateCommand(),
            new UpdateCommand(),
            new DropCommand(),
            new ValidateSchemaCommand(),
            new InfoCommand(),
        ]);
    }

    /**
     * Gets the default commands that should always be available.
     * @return Command[] An array of default Command instances
     * @throws \Exception
     */
    protected function getDefaultCommands()
    {
        $commands = $this->kernel->get('console.commands');
        /**
         * Register user defined Commands
         * Add them in your config.yml
         * @deprecated Use a service provider then add it to your AppKernel
         */
        try {
            if (isset($this->kernel->get('config')['additionalCommands'])) {
                foreach ($this->kernel->get('config')['additionalCommands'] as $commandClass) {
                    if (class_exists($commandClass)) {
                        $commands[] = new $commandClass();
                    } else {
                        throw new \Exception("Command class does not exists (" . $commandClass . ")", 1);
                    }
                }
            }
        } catch (NoConfigurationFoundException $e) {
            // Do not load additional commands if configuration is not available
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
        $helperSet->set(new ThemeResolverHelper($this->kernel->get('themeResolver')));
        $helperSet->set(new ConfigurationHandlerHelper($this->kernel->get('config_handler')));
        $helperSet->set(new AssetPackagesHelper($this->kernel->getContainer()));
        $helperSet->set(new CacheProviderHelper($this->kernel->get('nodesSourcesUrlCacheProvider')));

        /*
         * Configuration dependent helpers.
         */
        try {
            $helperSet->set(new ConfigurationHelper($this->kernel->get('config')));
            $helperSet->set(new MailerHelper($this->kernel->get('mailer')));
        } catch (NoConfigurationFoundException $e) {
            $helperSet->set(new ConfigurationHelper([]));
        }

        /*
         * Entity manager dependent helpers.
         */
        /** @var EntityManager $em */
        $em = $this->kernel->get('em');
        if (null !== $em) {
            try {
                $helperSet->set(new ConnectionHelper($em->getConnection()));
                // We need to set «em» alias as Doctrine misnamed its Helper :-(
                $helperSet->set(new EntityManagerHelper($em), 'em');
                $helperSet->set(new SolrHelper($this->kernel->get('solr')));
                $helperSet->set(new HandlerFactoryHelper($this->kernel->get('factory.handler')));
                $helperSet->set(new RolesBagHelper($this->kernel->get('rolesBag')));
            } catch (ConnectionException $exception) {
            } catch (\PDOException $exception) {
            }
        }

        return $helperSet;
    }
}
