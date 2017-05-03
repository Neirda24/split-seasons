<?php

namespace Split;

use InvalidArgumentException;
use LogicException;
use stdClass;
use Symfony\Component\Console\Exception\LogicException as ConsoleLogicException;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class MediaMover
{
    const MOVIE_TYPES = [
        'avi',
        'mkv',
        'mp4'
    ];
    
    /**
     * @var string
     */
    private $storeRootPath;
    
    /**
     * @var string
     */
    private $episodesRootPath;
    
    /**
     * @var string
     */
    private $serieName;
    
    /**
     * @var JsonParser
     */
    private $jsonParser;
    
    /**
     * @var integer
     */
    private $count;
    
    /**
     * DirectoriesCreator constructor.
     *
     * @param string     $storeRootPath
     * @param string     $episodesRootPath
     * @param string     $serieName
     * @param JsonParser $jsonParser
     *
     * @throws InvalidArgumentException
     */
    public function __construct($storeRootPath, $episodesRootPath, $serieName, JsonParser $jsonParser)
    {
        $this->setStoreRootPath($storeRootPath);
        $this->setEpisodesRootPath($episodesRootPath);
        $this->serieName  = $serieName;
        $this->jsonParser = $jsonParser;
    }
    
    /**
     * @return string
     */
    private function getStoreRootPath()
    {
        return rtrim($this->storeRootPath, '/');
    }
    
    /**
     * @param string $storeRootPath
     *
     * @return $this
     * @throws InvalidArgumentException
     */
    private function setStoreRootPath($storeRootPath)
    {
        if ('' === trim($storeRootPath)) {
            throw new InvalidArgumentException(sprintf('String expected. Got [%s] instead.', gettype($storeRootPath)));
        }
        $storeRootPath = realpath($storeRootPath);
        if (!is_dir($storeRootPath)) {
            throw new InvalidArgumentException(sprintf('[%s] does not exists or is a file. Directory path expected.', $storeRootPath));
        }
        $this->storeRootPath = $storeRootPath;
        
        return $this;
    }
    
    /**
     * @return string
     */
    private function getEpisodesRootPath()
    {
        return rtrim($this->episodesRootPath, '/');
    }
    
    /**
     * @param string $episodesRootPath
     *
     * @return $this
     * @throws InvalidArgumentException
     */
    private function setEpisodesRootPath($episodesRootPath)
    {
        if ('' === trim($episodesRootPath)) {
            throw new InvalidArgumentException(sprintf('String expected. Got [%s] instead.', gettype($episodesRootPath)));
        }
        $episodesRootPath = realpath($episodesRootPath);
        if (!is_dir($episodesRootPath)) {
            throw new InvalidArgumentException(sprintf('[%s] does not exists or is a file. Directory path expected.', $episodesRootPath));
        }
        $this->episodesRootPath = $episodesRootPath;
        
        return $this;
    }
    
    /**
     * @param bool        $keepSeq
     * @param ProgressBar $progress
     *
     * @throws FileNotFoundException
     * @throws IOException
     * @throws ConsoleLogicException
     * @throws InvalidArgumentException
     * @throws LogicException
     */
    public function move($keepSeq = false, ProgressBar $progress = null)
    {
        $fs        = new Filesystem();
        $currentEp = 0;
        
        foreach ($this->jsonParser->getSeasons() as $season) {
            $directoryName = $this->jsonParser->getDirectory($season);
            if (false === $keepSeq) {
                $currentEp = 0;
            }
            for ($i = $season->from; $i <= $season->to; $i++) {
                $episode = $this->findFile($season, $i);
                $currentEp++;
                if (false === $episode) {
                    if (null !== $progress) {
                        $progress->advance();
                    }
                    continue;
                }
                
                $epName = sprintf(
                    '%s - s%02de%04d.%s',
                    $this->serieName,
                    $season->no,
                    $currentEp,
                    $episode->getExtension()
                );
                $episodeNewPath = $this->getStoreRootPath() . '/' . $directoryName . '/' . $epName;
                if ((string)$episode !== $episodeNewPath) {
                    $fs->rename($episode, $episodeNewPath);
                }
                if (null !== $progress) {
                    $progress->advance();
                }
            }
        }
    }
    
    /**
     * @param stdClass $season
     * @param integer  $episode
     *
     * @return SplFileInfo
     * @throws LogicException
     * @throws InvalidArgumentException
     */
    private function findFile(stdClass $season, $episode)
    {
        $pattern = sprintf(
            '#^%s[^0-9]*(?:s(?:0?)*%de(?:0?)*%d|(?:0?)*%d)\.(?:%s)$#i',
            $this->serieName,
            $season->no,
            ($episode - $season->from + 1),
            $episode,
            implode('|', self::MOVIE_TYPES)
        );
        
        $finder = new Finder();
        $finder
            ->files()
            ->name($pattern)
            ->in($this->getEpisodesRootPath());
        $found = iterator_to_array($finder->getIterator());
        
        return reset($found);
    }
    
    /**
     * @return int
     */
    public function count()
    {
        if (is_int($this->count)) {
            return $this->count;
        }
        
        $total = 0;
        
        foreach ($this->jsonParser->getSeasons() as $season) {
            $total += (($season->to - $season->from) + 1);
        }
        
        $this->count = $total;
        
        return $this->count;
    }
}
