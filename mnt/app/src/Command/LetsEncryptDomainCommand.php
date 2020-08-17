<?php

namespace App\Command;

use Elphin\PHPCertificateToolbox\DiagnosticLogger;
use Elphin\PHPCertificateToolbox\LEClient;
use Elphin\PHPCertificateToolbox\LEOrder;
use GuzzleHttp\Client;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class LetsEncryptDomainCommand extends Command
{
    public Filesystem $filesystem;
    public Client $httpClient;
    public DiagnosticLogger $logger;


    public function __construct()
    {
        $this->logger = new DiagnosticLogger;
        $this->filesystem = new Filesystem();
        $this->httpClient = new Client([
            'curl'            => [
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false
            ],
            'verify'          => false
        ]);
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('app:letsencrypt:domain')
            ->setDescription('Сгенерировать сертификат от LetsEncrypt для необходимого домена')
            ->setHelp('Эта команда генерирует сертификат от LetsEncrypt для необходимого домена')
            ->addArgument('domain', InputArgument::REQUIRED, 'Домен');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $domain = $input->getArgument('domain');

        if(!preg_match("/^(([A-Z0-9][A-Z0-9_-]*)(\.[A-Z0-9][A-Z0-9_-]*)+)/i", $domain)){
            $output->writeln('<comment>Домен должен быть в формате domain.com</comment>');
            return 0;
        }

        $client = new LEClient([$_ENV['ACME_EMAIL']], true, $this->logger, $this->httpClient);

        $order = $client->getOrCreateOrder($domain, [$domain]);
        if(!$order->allAuthorizationsValid()){
            $pending = $order->getPendingAuthorizations(LEOrder::CHALLENGE_TYPE_HTTP);
            if(!empty($pending)){
                foreach($pending as $challenge) {
                    $this->filesystem->dumpFile($_ENV['ACME_PATH'] . $challenge['filename'], $challenge['content']);
                    $order->verifyPendingOrderAuthorization($challenge['identifier'], LEOrder::CHALLENGE_TYPE_HTTP);
                }
            }
        }
        if($order->allAuthorizationsValid()){
            if(!$order->isFinalized()) $order->finalizeOrder();
            if($order->isFinalized()) $order->getCertificate();
        }
        $this->logger->dumpConsole();

        return 1;
    }
}