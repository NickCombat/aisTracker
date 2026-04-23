<?php
// src/Command/AisStreamCommand.php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Service\AisStreamService;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand( name: 'app:ais-listen' )]
class AisStreamCommand extends Command
{

    public function __construct(
        private readonly HttpClientInterface    $httpClient,
        private readonly AisStreamService       $aisService
    )
    {
        parent::__construct();
    }

    protected function execute( InputInterface $input, OutputInterface $output ): int
    {
        $output->writeln( date('Y-m-d H:i:s ') . 'Starte AIS Stream...' );
        try
        {
            $this->aisService->listen();
        }
        catch (\Exception $e)
        {
            $output->writeln( date('Y-m-d H:i:s ') . 'Fehler AIS Stream:' . $e->getMessage() );
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}