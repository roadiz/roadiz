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

use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Core\Entities\Translation;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command line utils for managing translations from terminal.
 */
class TranslationsCommand extends Command
{
    private $dialog;

    protected function configure()
    {
        $this->setName('core:translations')
            ->setDescription('Manage translations')
            ->addArgument(
                'name',
                InputArgument::OPTIONAL,
                'Translation name'
            )
            ->addArgument(
                'locale',
                InputArgument::OPTIONAL,
                'Translation locale'
            )
            ->addOption(
                'create',
                null,
                InputOption::VALUE_NONE,
                'Create a translation'
            )
            ->addOption(
                'delete',
                null,
                InputOption::VALUE_NONE,
                'Delete requested translation'
            )
            ->addOption(
                'update',
                null,
                InputOption::VALUE_NONE,
                'Update requested translation'
            )
            ->addOption(
                'enable',
                null,
                InputOption::VALUE_NONE,
                'Enable requested translation'
            )
            ->addOption(
                'disable',
                null,
                InputOption::VALUE_NONE,
                'Disable requested translation'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->dialog = $this->getHelperSet()->get('dialog');
        $text="";
        $name = $input->getArgument('name');
        $locale = $input->getArgument('locale');

        if ($name) {
            $translation = Kernel::getService('em')
                ->getRepository('RZ\Roadiz\Core\Entities\Translation')
                ->findOneBy(array('name'=>$name));

            if ($translation !== null) {
                $text = $translation->getOneLineSummary();

                if ($input->getOption('delete')) {
                    if ($this->dialog->askConfirmation(
                        $output,
                        '<question>Are you sure to delete '.$translation->getName().' translation?</question> : ',
                        false
                    )) {
                        Kernel::getService('em')->remove($translation);
                        Kernel::getService('em')->flush();
                        $text = '<info>Translation deleted…</info>'.PHP_EOL;
                    }
                } elseif ($input->getOption('enable')) {
                    $translation->setAvailable(true);
                    Kernel::getService('em')->flush();

                    $text .= '<info>'.$translation->getName()." enabled…</info>".PHP_EOL;
                } elseif ($input->getOption('disable')) {
                    $translation->setAvailable(false);
                    Kernel::getService('em')->flush();

                    $text .= '<info>'.$translation->getName()." disabled…</info>".PHP_EOL;
                }
            } else {
                if ($input->getOption('create')) {
                    if (!empty($locale)) {
                        $newTrans = new Translation();
                        $newTrans->setName($name)
                                ->setLocale($locale);

                        Kernel::getService('em')->persist($newTrans);
                        Kernel::getService('em')->flush();

                        $text = 'New translation : '.$newTrans->getName().PHP_EOL.
                        'Locale : '.$newTrans->getLocale().PHP_EOL.
                        'Available: '.(string) $newTrans->isAvailable().PHP_EOL;

                    } else {
                        $text = '<error>You must define a locale…</error>'.PHP_EOL;
                    }

                }
            }
        } else {
            $text = '<info>Existing translations…</info>'.PHP_EOL;
            $translations = Kernel::getService('em')
                ->getRepository('RZ\Roadiz\Core\Entities\Translation')
                ->findAll();

            if (count($translations) > 0) {
                foreach ($translations as $trans) {
                    $text .= $trans->getOneLineSummary();
                }
            } else {
                $text = '<info>No available translations…</info>'.PHP_EOL;
            }
        }

        $output->writeln($text);
    }
}
