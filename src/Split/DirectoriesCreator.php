<?php

namespace Split;

use InvalidArgumentException;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

class DirectoriesCreator
{
    /**
     * @var string
     */
    private $storeRootPath;
    
    /**
     * @var string
     */
    private $episodesRootPath;
    
    /**
     * @var JsonParser
     */
    private $jsonParser;
    
    /**
     * DirectoriesCreator constructor.
     *
     * @param string     $storeRootPath
     * @param string     $episodesRootPath
     * @param JsonParser $jsonParser
     *
     * @throws InvalidArgumentException
     */
    public function __construct($storeRootPath, $episodesRootPath, JsonParser $jsonParser)
    {
        $this->setStoreRootPath($storeRootPath);
        $this->setEpisodesRootPath($episodesRootPath);
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
            throw new InvalidArgumentException(sprintf('[%s]String expected. Got [%s] instead.', __METHOD__, gettype($storeRootPath)));
        }
        $storeRootPath = realpath($storeRootPath);
        if (!is_dir($storeRootPath)) {
            throw new InvalidArgumentException(sprintf('[%s][%s] does not exists or is a file. Directory path expected.', __METHOD__, $storeRootPath));
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
            throw new InvalidArgumentException(sprintf('[%s]String expected. Got [%s] instead.', __METHOD__, gettype($episodesRootPath)));
        }
        $episodesRootPath = realpath($episodesRootPath);
        if (!is_dir($episodesRootPath)) {
            throw new InvalidArgumentException(sprintf('[%s][%s] does not exists or is a file. Directory path expected.', __METHOD__, $episodesRootPath));
        }
        $this->episodesRootPath = $episodesRootPath;
        
        return $this;
    }
    
    /**
     * Create the directories of the Seasons
     *
     * @return void
     * @throws IOException
     */
    public function create()
    {
        $fs          = new Filesystem();
        $directories = $this->jsonParser->getDirectories();
        foreach ($directories as $directory) {
            $fs->mkdir($this->getStoreRootPath() . '/' . $directory);
        }
    }
}
