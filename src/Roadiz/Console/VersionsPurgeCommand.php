<?php
declare(strict_types=1);

namespace RZ\Roadiz\Console;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use RZ\Roadiz\Core\Entities\UserLogEntry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

class VersionsPurgeCommand extends Command
{
    protected function configure()
    {
        $this->setName('versions:purge')
            ->setDescription('Purge entities versions')
            ->setHelp(<<<EOT
Purge entities versions <info>before</info> a given date-time
OR by keeping at least <info>count</info> versions.

This command does not alter active node-sources, document translations
or tag translations, it only deletes versioned log entries.
EOT
            )
            ->addOption(
                'before',
                'b',
                InputOption::VALUE_REQUIRED,
                'Purge versions older than <info>before</info> date <info>(any format accepted by \DateTime)</info>.'
            )
            ->addOption(
                'count',
                'c',
                InputOption::VALUE_REQUIRED,
                'Keeps only <info>count</info> versions for each entities (count must be greater than 1).'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->hasOption('before') && $input->getOption('before') != '') {
            $this->purgeByDate($input, $output);
        } elseif ($input->hasOption('count')) {
            if ((int) $input->getOption('count') < 2) {
                throw new \InvalidArgumentException('Count option must be greater than 1.');
            }
            $this->purgeByCount($input, $output);
        } else {
            throw new \InvalidArgumentException('Choose an option between --before or --count');
        }
        return 0;
    }

    private function purgeByDate(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        /** @var EntityManagerInterface $em */
        $em = $this->getHelper('doctrine')->getEntityManager();
        $dateTime = new \DateTime($input->getOption('before'));

        if ($dateTime >= new \DateTime()) {
            throw new \InvalidArgumentException('Before date must be in the past.');
        }
        /** @var QueryBuilder $qb */
        $qb = $em->getRepository(UserLogEntry::class)->createQueryBuilder('l');
        $count = $qb->select($qb->expr()->countDistinct('l'))
            ->where($qb->expr()->lt('l.loggedAt', ':loggedAt'))
            ->setParameter('loggedAt', $dateTime)
            ->getQuery()
            ->getSingleScalarResult()
        ;
        $question = new ConfirmationQuestion(sprintf(
            'Do you want to purge <info>%s</info> version(s) before <info>%s</info>?',
            $count,
            $dateTime->format('c')
        ), false);
        if (!$input->isInteractive() || $io->askQuestion(
            $question
        )) {
            /** @var QueryBuilder $qb */
            $qb = $em->getRepository(UserLogEntry::class)->createQueryBuilder('l');
            $result = $qb->delete(UserLogEntry::class, 'l')
                ->where($qb->expr()->lt('l.loggedAt', ':loggedAt'))
                ->setParameter('loggedAt', $dateTime)
                ->getQuery()
                ->execute()
            ;
            $io->success(sprintf('%s version(s) were deleted.', $result));
        }
    }

    private function purgeByCount(InputInterface $input, OutputInterface $output)
    {
        $deleteCount = 0;
        $io = new SymfonyStyle($input, $output);
        $count = (int) $input->getOption('count');
        /** @var EntityManagerInterface $em */
        $em = $this->getHelper('doctrine')->getEntityManager();

        $question = new ConfirmationQuestion(sprintf(
            'Do you want to purge all entities versions and to keep only the <info>latest %s</info>?',
            $count
        ), false);
        if (!$input->isInteractive() || $io->askQuestion(
            $question
        )) {
            /** @var QueryBuilder $qb */
            $qb = $em->getRepository(UserLogEntry::class)->createQueryBuilder('l');
            $objects = $qb->select('MAX(l.version) as maxVersion', 'l.objectId', 'l.objectClass')
                ->groupBy('l.objectId', 'l.objectClass')
                ->getQuery()
                ->getArrayResult()
            ;
            $deleteQuery = $qb->delete(UserLogEntry::class, 'l')
                ->andWhere($qb->expr()->eq('l.objectId', ':objectId'))
                ->andWhere($qb->expr()->eq('l.objectClass', ':objectClass'))
                ->andWhere($qb->expr()->lt('l.version', ':lowestVersion'))
                ->getQuery()
            ;

            foreach ($objects as $object) {
                $lowestVersion = (int) $object['maxVersion'] - $count;
                if ($lowestVersion > 1) {
                    $deleteCount += $deleteQuery->execute([
                        'objectId' => $object['objectId'],
                        'objectClass' => $object['objectClass'],
                        'lowestVersion' => $lowestVersion
                    ]);
                }
            }

            $io->success(sprintf('%s version(s) were deleted.', $deleteCount));
        }
    }
}
