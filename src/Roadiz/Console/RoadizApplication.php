<?php
declare(strict_types=1);

namespace RZ\Roadiz\Console;

use Doctrine\DBAL\Exception\ConnectionException;
use Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper;
use Doctrine\Migrations\Configuration\Connection\ExistingConnection;
use Doctrine\Migrations\Configuration\EntityManager\EntityManagerLoader;
use Doctrine\Migrations\Configuration\EntityManager\ExistingEntityManager;
use Doctrine\Migrations\Configuration\Migration\ConfigurationArray;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Tools\Console\ConsoleRunner;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Console\Command\InfoCommand;
use Doctrine\ORM\Tools\Console\Command\SchemaTool\CreateCommand;
use Doctrine\ORM\Tools\Console\Command\SchemaTool\DropCommand;
use Doctrine\ORM\Tools\Console\Command\SchemaTool\UpdateCommand;
use Doctrine\ORM\Tools\Console\Command\ValidateSchemaCommand;
use Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper;
use RZ\Roadiz\Config\ConfigurationHandlerInterface;
use RZ\Roadiz\Core\ContainerAwareInterface;
use RZ\Roadiz\Core\Exceptions\NoConfigurationFoundException;
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
use Symfony\Component\HttpFoundation\Request;

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
        try {
            /** @var EntityManagerInterface $entityManager */
            $entityManager = $this->kernel->get('em');
            \Doctrine\ORM\Tools\Console\ConsoleRunner::addCommands($this);
            ConsoleRunner::addCommands($this, $this->kernel->get(DependencyFactory::class));
        } catch (ConnectionException $exception) {
        } catch (\PDOException $exception) {
        }
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

        $commands = array_merge(parent::getDefaultCommands(), $commands);

        foreach ($commands as $command) {
            if ($command instanceof ContainerAwareInterface) {
                $command->setContainer($this->kernel->getContainer());
            }
        }

        return $commands;
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
        $helperSet->set(new ConfigurationHandlerHelper($this->kernel->get(ConfigurationHandlerInterface::class)));
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
