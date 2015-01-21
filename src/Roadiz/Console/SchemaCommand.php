<?php
/**
 * Copyright © 2014, Ambroise Maupate and Julien Blanchet
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
 * @file SchemaCommand.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Console;

use RZ\Roadiz\Core\Kernel;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

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
            )
            ->addOption(
                'delete',
                null,
                InputOption::VALUE_NONE,
                'Apply changes including deletions (you may lose data)'
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
                 * If execute option = Perform changes
                 */
                if ($input->getOption('execute') &&
                    $input->getOption('delete')) {
                    if ($this->dialog->askConfirmation(
                        $output,
                        'Deletions may remove some of your data.'.PHP_EOL.'Have you done a database backup before?'.PHP_EOL.'<question>Are you sure to update your database schema? [y / N]</question> : ',
                        false
                    )) {
                        if (static::updateSchema(true)) {
                            $text .= '<info>Schema updated…</info>'.PHP_EOL;
                        }
                    } else {
                        $text .= '<info>Schema update aborted</info>'.PHP_EOL;
                    }
                } elseif ($input->getOption('execute')) {
                    /*
                     * If execute option = Perform changes
                     */
                    if ($this->dialog->askConfirmation(
                        $output,
                        '<question>Are you sure to update your database schema? [y / N]</question> : ',
                        false
                    )) {
                        if (static::updateSchema()) {
                            $text .= '<info>Schema updated…</info>'.PHP_EOL;
                        }
                    } else {
                        $text .= '<info>Schema update aborted</info>'.PHP_EOL;
                    }
                } else {
                    /*
                     * Print changes
                     */
                    $text .= '<info>'.$count.'</info> change(s) in your database schema… Use <info>--execute</info> to apply only new changes with no deletions:'.PHP_EOL;
                    $deletions = [];
                    for ($i=0; $i<$count; $i++) {
                        if (substr($sql[$i], 0, 6) == 'DELETE' ||
                            strpos($sql[$i], 'DROP')) {
                            $deletions[] = $sql[$i];
                        } else {
                            $text .= $sql[$i].PHP_EOL;
                        }
                    }

                    if (count($deletions) > 0) {
                        $text .= '<info>'.count($deletions).'</info> deletion(s) will be performed! Use <info>--execute --delete</info> to apply:'.PHP_EOL;
                        foreach ($deletions as $statement) {
                            $text .= $statement.PHP_EOL;
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
     * @param boolean $delete Enable DELETE and DROP statements
     *
     * @return boolean
     */
    public static function updateSchema($delete = false)
    {
        CacheCommand::clearDoctrine();

        $tool = new SchemaTool(Kernel::getService('em'));
        $meta = Kernel::getService('em')->getMetadataFactory()->getAllMetadata();

        $sql = $tool->getUpdateSchemaSql($meta, true);
        $deletions = [];

        foreach ($sql as $statement) {
            if (substr($statement, 0, 6) == 'DELETE' ||
                strpos($statement, 'DROP')) {
                $deletions[] = $statement;
            } else {
                Kernel::getService('em')->getConnection()->exec($statement);
            }
        }

        if (true === $delete) {
            foreach ($deletions as $statement) {
                Kernel::getService('em')->getConnection()->exec($statement);
            }
        }

        return true;
    }

    /**
     * Create database schema.
     */
    public static function createSchema()
    {
        $tool = new SchemaTool(Kernel::getService('em'));
        $meta = Kernel::getService('em')->getMetadataFactory()->getAllMetadata();

        $tool->createSchema($meta);
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

        return $tool->getUpdateSchemaSql($meta, true);
    }
}
