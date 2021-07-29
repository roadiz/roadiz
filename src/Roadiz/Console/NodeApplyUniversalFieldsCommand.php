<?php
declare(strict_types=1);

namespace RZ\Roadiz\Console;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NoResultException;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Utils\Node\UniversalDataDuplicator;
use Symfony\Component\Console\Command\Command;
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
        $this->entityManager = $this->getHelper('doctrine')->getEntityManager();

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
        return 0;
    }
}
