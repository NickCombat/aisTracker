<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use App\Entity\NetShipdata;

#[AsCommand(
    name: 'app:ship-enrich',
    description: 'Add a short description for your command',
)]
class ShipEnrichCommand extends Command
{
    private EntityManagerInterface $entityManager;

    private $shipRepository;

    public function __construct( EntityManagerInterface $em)
    {
        $this->entityManager         = $em;

        $this->shipRepository = $this->entityManager->getRepository(NetShipdata::class);

        parent::__construct();
    }

    protected function configure(): void
    {}

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // 1. Hole alle Schiffe aus der Datenbank, die noch keine IMO/Stammdaten haben
        $ships = $this->shipRepository->findBy(['imo' => 0], null, 50); // Immer nur Pakete abarbeiten (z.B. 50)

        if (empty($ships)) {
            $output->writeln('Keine Schiffe zur Anreicherung gefunden.');
            return Command::SUCCESS;
        }

        foreach ($ships as $ship)
        {
            // 2. Frage eine externe API (z.B. VesselFinder, Datalastic) nach der MMSI ab
            // WICHTIG: Baue hier ein sleep(1) oder sleep(2) ein, um Rate-Limits der APIs nicht zu sprengen!
            $metaData = $this->externalApiService->fetchShipData($ship->getMmsi());

            if ($metaData)
            {
                $ship->setImo($metaData['imo']);
                $ship->setType($metaData['type']);
                // ... weitere Daten setzen
            }
        }

        // 3. Änderungen in die Datenbank schreiben
        $this->entityManager->flush();

        $output->writeln(sprintf('%d Schiffe erfolgreich angereichert.', count($ships)));
        return Command::SUCCESS;
    }
}
