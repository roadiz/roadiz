<?php
/**
 * Copyright © 2016, Ambroise Maupate and Julien Blanchet
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
 * @file TranslationsCreationCommand.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Console;

use RZ\Roadiz\Core\Entities\Translation;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Command line utils for managing translations
 */
class TranslationsCreationCommand extends Command
{
    private $questionHelper;
    private $entityManager;

    protected function configure()
    {
        $this->setName('translations:create')
            ->setDescription('Create a translation')
            ->addArgument(
                'name',
                InputArgument::REQUIRED,
                'Translation name'
            )
            ->addArgument(
                'locale',
                InputArgument::REQUIRED,
                'Translation locale'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->questionHelper = $this->getHelper('question');
        $this->entityManager = $this->getHelper('entityManager')->getEntityManager();
        $text = "";
        $name = $input->getArgument('name');
        $locale = $input->getArgument('locale');

        if ($name) {
            $translationByName = $this->entityManager
                ->getRepository('RZ\Roadiz\Core\Entities\Translation')
                ->findOneByName($name);
            $translationByLocale = $this->entityManager
                ->getRepository('RZ\Roadiz\Core\Entities\Translation')
                ->findOneByLocale($locale);

            $confirmation = new ConfirmationQuestion(
                '<question>Are you sure to create ' . $name . ' (' . $locale . ') translation?</question> [y/N]:',
                false
            );

            if (null !== $translationByName) {
                $text .= '<error>Translation ' . $name . ' already exists.</error>' . PHP_EOL;
            } elseif (null !== $translationByLocale) {
                $text .= '<error>Translation locale ' . $locale . ' is already used.</error>' . PHP_EOL;
            } else {
                if ($this->questionHelper->ask(
                    $input,
                    $output,
                    $confirmation
                )) {
                    $newTrans = new Translation();
                    $newTrans->setName($name)
                        ->setLocale($locale);

                    $this->entityManager->persist($newTrans);
                    $this->entityManager->flush();

                    $text = 'New <info>' . $newTrans->getName() . '</info> translation for <info>' . $newTrans->getLocale() . '</info> locale.' . PHP_EOL;
                }
            }
        }

        $output->writeln($text);
    }
}
