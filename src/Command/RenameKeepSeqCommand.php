<?php

namespace Command;

use Neirda24\Bundle\ToolsBundle\Utils\PathUtils;
use Split\JsonParser;
use Split\MediaRenamer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RenameKeepSeqCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('rename:keep-seq')
            ->setDescription('Rename episodes in seasons so that it will keep the end of previous season as straing number.')
            ->addArgument('name', InputArgument::REQUIRED, 'Name to apply to the serie.')
            ->addArgument('seasons', InputArgument::REQUIRED, 'Path to the json file containing the seasons.')
            ->addArgument('episodes', InputArgument::REQUIRED, 'Path to the directory that contains the episodes.')
            ->setHelp(<<<EOF

EOF
            );
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $seasons   = $input->getArgument('seasons');
        $store     = $input->getArgument('episodes');
        $episodes  = $input->getArgument('episodes');
        $serieName = $input->getArgument('name');
        
        $seasons = PathUtils::cleanHomeVariable($seasons);
        $seasons = realpath($seasons);
        
        $store = PathUtils::cleanHomeVariable($store);
        $store = realpath($store);
        
        $episodes = PathUtils::cleanHomeVariable($episodes);
        $episodes = realpath($episodes);
        
        $jsonParser = new JsonParser($seasons);
        
        $mediaMover = new MediaRenamer($store, $episodes, $serieName, $jsonParser);
    
        $progress = new ProgressBar($output, $mediaMover->count());
        $progress->start();
        
        $mediaMover->rename(true, $progress);
    
        $progress->finish();
    }
}
