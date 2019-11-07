<?php
/**
 * Copyright (c) 2016.
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
 * @file NodeApplyUniversalFieldsCommand.php
 * @author ambroisemaupate
 */
namespace RZ\Roadiz\Console;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NoResultException;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Utils\Node\UniversalDataDuplicator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

class NodeApplyUniversalFieldsCommand extends Command
{
    /** @var  EntityManager*/
    private $entityManager;

    protected function configure()
    {
        $this->setName('nodes:force-universal')
            ->setDescription('Clean every nodes universal fields getting value form their default translation.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->entityManager = $this->getHelper('entityManager')->getEntityManager();

        $translation = $this->entityManager->getRepository(Translation::class)
                            ->findDefault();

        $io = new SymfonyStyle($input, $output);

        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('ns')
            ->distinct(true)
            ->from(NodesSources::class, 'ns')
            ->innerJoin('ns.node', 'n')
            ->innerJoin('n.nodeType', 'nt')
            ->innerJoin('nt.fields', 'ntf')
            ->andWhere($qb->expr()->eq('ns.translation', ':translation'))
            ->andWhere($qb->expr()->eq('ntf.universal', true))
            ->setParameter(':translation', $translation);
        try {
            $sources = $qb->getQuery()->getResult();
            $io->note(count($sources).' node(s) with universal fields were found.');

            $question = new ConfirmationQuestion(
                '<question>Are you sure to force every universal fields?</question>',
                false
            );
            if ($io->askQuestion(
                $question
            )) {
                $duplicator = new UniversalDataDuplicator($this->entityManager);
                $io->progressStart(count($sources));

                /** @var NodesSources $source */
                foreach ($sources as $source) {
                    $duplicator->duplicateUniversalContents($source);
                    $io->progressAdvance();
                }
                $this->entityManager->flush();
                $io->progressFinish();
            }
        } catch (NoResultException $e) {
            $io->warning('No node with universal fields were found.');
        }
    }
}
