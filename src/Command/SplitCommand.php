<?php

namespace Command;

use Neirda24\Bundle\ToolsBundle\Utils\PathUtils;
use Split\DirectoriesCreator;
use Split\JsonParser;
use Split\MediaMover;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SplitCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('split')
            ->setDescription('Split episodes into seasons')
            ->addArgument('name', InputArgument::REQUIRED, 'Name to apply to the serie.')
            ->addArgument('seasons', InputArgument::REQUIRED, 'Path to the json file containing the seasons.')
            ->addArgument('store', InputArgument::REQUIRED, 'Path to the directory that will store the seasons.')
            ->addArgument('episodes', InputArgument::REQUIRED, 'Path to the directory that contains the episodes.')
            ->addOption('keep-seq', null, InputOption::VALUE_NONE, 'To keep the episodes number increasing on each season')
            ->setHelp(<<<EOF

EOF
            );
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $seasons   = $input->getArgument('seasons');
        $store     = $input->getArgument('store');
        $episodes  = $input->getArgument('episodes');
        $serieName = $input->getArgument('name');
        $keepSeq = $input->hasOption('keep-seq') && $input->getOption('keep-seq');
        
        $seasons = PathUtils::cleanHomeVariable($seasons);
        $seasons = realpath($seasons);
        
        $store = PathUtils::cleanHomeVariable($store);
        $store = realpath($store);
        
        $episodes = PathUtils::cleanHomeVariable($episodes);
        $episodes = realpath($episodes);
        
        $jsonParser = new JsonParser($seasons);
        
        $seasonDirectoryCreator = new DirectoriesCreator($store, $episodes, $jsonParser);
        $seasonDirectoryCreator->create();
        
        $mediaMover = new MediaMover($store, $episodes, $serieName, $jsonParser);
    
        $progress = new ProgressBar($output, $mediaMover->count());
        $progress->start();
        
        $mediaMover->move($keepSeq, $progress);
    
        $progress->finish();
    }
}
