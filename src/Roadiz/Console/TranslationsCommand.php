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
 * @file TranslationsCommand.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command line utils for managing translations from terminal.
 */
class TranslationsCommand extends Command
{
    private $entityManager;

    protected function configure()
    {
        $this->setName('translations:list')
            ->setDescription('List translations');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->entityManager = $this->getHelperSet()->get('em')->getEntityManager();
        $translations = $this->entityManager
            ->getRepository('RZ\Roadiz\Core\Entities\Translation')
            ->findAll();

        if (count($translations) > 0) {
            $table = new Table($output);
            $table->setHeaders(['Id', 'Name', 'Locale', 'Disabled', 'Default']);
            $tableContent = [];
            foreach ($translations as $trans) {
                $tableContent[] = [
                    $trans->getId(),
                    $trans->getName(),
                    $trans->getLocale(),
                    (!$trans->isAvailable() ? 'X' : ''),
                    ($trans->isDefaultTranslation() ? 'X' : ''),
                ];
            }
            $table->setRows($tableContent);
            $table->render();
        } else {
            $output->writeln('<info>No available translations.</info>' . PHP_EOL);
        }
    }
}
