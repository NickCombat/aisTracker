<?php
namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\Migrations\DependencyFactory;

#[AsCommand(name: 'app:force-migration')]
class ForceMigrationCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Hole Doctrine DependencyFactory via Public Alias...');

        // Wir greifen auf unseren neuen Alias zu
        /** @var DependencyFactory $dependencyFactory */
        $dependencyFactory = $this->getApplication()->getKernel()->getContainer()->get('app.public_dependency_factory');

        $output->writeln('Generiere Migration direkt über die API...');

        $diffGenerator = $dependencyFactory->getDiffGenerator();
        $path = $diffGenerator->generate('DoctrineMigrations\\Version' . date('YmdHis'), null, true);

        $output->writeln('Erfolgreich! Migration erstellt unter: ' . $path);
        return Command::SUCCESS;
    }
}