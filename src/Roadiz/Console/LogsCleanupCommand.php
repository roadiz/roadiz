<?php
declare(strict_types=1);

namespace RZ\Roadiz\Console;

use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;
use RZ\Roadiz\Core\Entities\Log;
use RZ\Roadiz\Core\Repositories\LogRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class LogsCleanupCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('logs:cleanup')
            ->setDescription('Clean up logs entries <info>older than 6 months</info> from database.')
            ->addOption('erase', null, InputOption::VALUE_NONE, 'Actually delete outdated log entries.')
        ;
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $now = new \DateTime('now');
        $now->add(\DateInterval::createFromDateString('-6 months'));
        $io = new SymfonyStyle($input, $output);

        /** @var ManagerRegistry $managerRegistry */
        $managerRegistry = $this->getHelper('doctrine')->getManagerRegistry();

        /** @var LogRepository $logRepository */
        $logRepository = $managerRegistry->getRepository(Log::class);
        $qb = $logRepository->createQueryBuilder('l');
        $qb->select($qb->expr()->count('l'))
            ->andWhere($qb->expr()->lte('l.datetime', ':date'))
            ->setParameter(':date', $now)
        ;

        try {
            $logs = $qb->getQuery()->getSingleScalarResult();
        } catch (NoResultException $e) {
            $logs = 0;
        }

        $io->note($logs . ' log entries found before '. $now->format('Y-m-d') . '.');

        if ($input->getOption('erase') && $logs > 0) {
            $qb2 = $logRepository->createQueryBuilder('l');
            $qb2->delete()
                ->andWhere($qb->expr()->lte('l.datetime', ':date'))
                ->setParameter(':date', $now)
            ;
            try {
                $numDeleted = $qb2->getQuery()->execute();
                $io->success($numDeleted.' log entries were deleted.');
            } catch (NoResultException $e) {
                $io->writeln('No log entries were deleted.');
            }
        }
        return 0;
    }
}
