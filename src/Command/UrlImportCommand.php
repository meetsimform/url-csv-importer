<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use App\Service\PageCsvImporter;

class UrlImportCommand extends Command
{
    private $psi;

    public function __construct(PageCsvImporter $psi)
    {
        $this->psi = $psi;
        parent::__construct();
    }

    // In this function set the name, description and help hint for the command
    protected function configure(): void
    {
        // Use in-build functions to set name, description and help
        $this->setName('import-urls')
            ->setDescription('This command imports the url form csv file');
    }

    // write the code you want to execute when command runs
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Start processing to import urls');
        $this->psi->prepareProcessUrlImporter();
        $output->writeln('Ends processing to import urls');
        return Command::SUCCESS;
    }
}
