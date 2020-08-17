<?php

namespace App\Command;

use App\Entity\DomainParking;
use App\Service\CronHistoryChecker;
use App\Service\PeriodMapper\CurrentWeek;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AutoLetsEncryptDomainCommand extends Command
{
    use LockableTrait;

    /** @var EntityManagerInterface */
    public $entityManager;
    const CRON_HISTORY_SLUG = 'letsencrypt-generate';


    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function configure()
    {
        $this
            ->setName('app:auto-letsencrypt:domain');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if(!$this->lock()){
            $output->writeln('The command is already running in another process.');

            return 0;
        }

        $cronHistoryChecker = new CronHistoryChecker($this->entityManager);

        $currentWeek = new CurrentWeek();
        [$from, $to] = $currentWeek->getDateBetween();

        $domains = $this->entityManager->getRepository(DomainParking::class)->getDomainsNeedCert($from, $to);

        foreach($domains as $domain) {
            if($this->executeLetsEncrypt($domain->getDomain(), $output)){
                $domain->setCertEndDate(new \DateTime('+90 days'));
                $this->entityManager->flush();
            }
        }

        $this->release();

        $cronHistoryChecker->create(self::CRON_HISTORY_SLUG);

        return 0;
    }

    private function executeLetsEncrypt(string $domain, OutputInterface $output)
    {
        $command = $this->getApplication()->find('app:letsencrypt:domain');

        $arguments = [
            'command' => 'app:letsencrypt:domain',
            'domain' => $domain,
        ];

        $greetInput = new ArrayInput($arguments);
        /** @var bool $returnCode */
        $returnCode = $command->run($greetInput, $output);

        return $returnCode;
    }
}