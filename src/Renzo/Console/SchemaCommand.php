<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 *
 * @file SchemaCommand.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */

namespace RZ\Renzo\Console;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\NodeType;
use RZ\Renzo\Core\Entities\NodeTypeField;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

use Doctrine\ORM\Events;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;

/**
 * Command line utils for managing database schema from terminal.
 */
class SchemaCommand extends Command
{
    private $dialog;

    protected function configure()
    {
        $this->setName('schema')
            ->setDescription('Manage database schema')
            ->addOption(
                'update',
                null,
                InputOption::VALUE_NONE,
                'Update current database schema'
            )
            ->addOption(
                'execute',
                null,
                InputOption::VALUE_NONE,
                'Apply changes'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->dialog = $this->getHelperSet()->get('dialog');
        $text="";

        if ($input->getOption('update')) {

            $sql = static::getUpdateSchema();
            $count = count($sql);

            if ($count > 0) {
                /*
                 * Print changes
                 */
                for ($i=0; $i<$count; $i++) {
                    $text .= $sql[$i].PHP_EOL;
                }
                $text .= '<info>'.$count.'</info> change(s) in your database schema… Use <info>--execute</info> to apply'.PHP_EOL;

                /*
                 * If execute option = Perform changes
                 */
                if ($input->getOption('execute')) {
                    if ($this->dialog->askConfirmation(
                        $output,
                        '<question>Are you sure to update your database schema?</question> : ',
                        false
                    )) {

                        if (static::updateSchema()) {
                            $text .= '<info>Schema updated…</info>'.PHP_EOL;
                        }
                    }
                }
            } else {
                $text .= '<info>Your database schema is already up to date…</info>'.PHP_EOL;
            }
        }

        $output->writeln($text);
    }

    /**
     * Update database schema.
     *
     * @return boolean
     */
    public static function updateSchema()
    {
        CacheCommand::clearDoctrine();

        $tool = new \Doctrine\ORM\Tools\SchemaTool(Kernel::getService('em'));
        $meta = Kernel::getService('em')->getMetadataFactory()->getAllMetadata();
        $sql = $tool->getUpdateSchemaSql($meta);

        foreach ($sql as $statement) {
            Kernel::getService('em')->getConnection()->exec($statement);
        }

        return true;
    }

    /**
     * Create database schema.
     */
    public static function createSchema()
    {
        $tool = new \Doctrine\ORM\Tools\SchemaTool(Kernel::getService('em'));
        $meta = Kernel::getService('em')->getMetadataFactory()->getAllMetadata();
        $sql = $tool->getUpdateSchemaSql($meta);

        foreach ($sql as $statement) {
            Kernel::getService('em')->getConnection()->exec($statement);
        }
    }

    /**
     * Get SQL query to update schema.
     *
     * @return string
     */
    public static function getUpdateSchema()
    {
        CacheCommand::clearDoctrine();

        $tool = new \Doctrine\ORM\Tools\SchemaTool(Kernel::getService('em'));
        $meta = Kernel::getService('em')->getMetadataFactory()->getAllMetadata();

        return $tool->getUpdateSchemaSql($meta);
    }
}
