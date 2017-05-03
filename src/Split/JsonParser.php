<?php

namespace Split;

use InvalidArgumentException;
use JsonSchema\Exception\ExceptionInterface;
use JsonSchema\Validator;
use LogicException;
use stdClass;

class JsonParser
{
    /**
     * @var array
     */
    private $data;
    
    /**
     * @var string[]
     */
    private $directories;
    
    /**
     * JsonParser constructor.
     *
     * @param string $jsonPath
     *
     * @throws InvalidArgumentException
     * @throws ExceptionInterface
     * @throws JsonValidationException
     * @throws LogicException
     */
    public function __construct($jsonPath)
    {
        $this->setData($jsonPath);
    }
    
    /**
     * @param string $jsonPath
     *
     * @return $this
     * @throws LogicException
     * @throws InvalidArgumentException
     * @throws ExceptionInterface
     * @throws JsonValidationException
     */
    private function setData($jsonPath)
    {
        if ('' === trim($jsonPath)) {
            throw new InvalidArgumentException(sprintf('[%s]String expected. Got [%s] instead.', __METHOD__, gettype($jsonPath)));
        }
        if (!is_file($jsonPath)) {
            throw new InvalidArgumentException(sprintf('[%s][%s] does not exists or is a directory. File path expected.', __METHOD__, $jsonPath));
        }
        
        $data       = json_decode(file_get_contents($jsonPath));
        $schemaData = json_decode(file_get_contents(APP_ROOT_DIR . '/resources/schema.json'));
        
        $validator = new Validator();
        $validator->check($data, $schemaData);
        
        if (!$validator->isValid()) {
            $errors = [];
            foreach ((array)$validator->getErrors() as $error) {
                $errors[] = ($error['property'] ? $error['property'] . ' : ' : '') . $error['message'];
            }
            throw new JsonValidationException('"' . $jsonPath . '" does not match the expected JSON schema', $errors);
        }
        
        $this->data = $data->seasons;
        $this->sortSeasons();
        $this->validateEpisodes();
        
        return $this;
    }
    
    /**
     * @return $this
     */
    private function sortSeasons()
    {
        $sortedSeason = [];
        
        foreach ($this->data as $season) {
            $sortedSeason[$season->no] = $season;
        }
        
        $this->data = $sortedSeason;
        ksort($this->data);
        
        return $this;
    }
    
    /**
     * @return bool
     * @throws LogicException
     */
    private function validateEpisodes()
    {
        $previousEndEpisode = -1;
        
        foreach ($this->data as $season) {
            $startEpisode = $season->from;
            $endEpisode   = $season->to;
            
            if ($endEpisode < $startEpisode) {
                throw new LogicException(sprintf(
                    '[Season %d - %s] End of season is before the start of it (%d < %d). WTF ?',
                    $season->no,
                    $season->title,
                    $endEpisode,
                    $startEpisode
                ));
            }
            
            if ($startEpisode <= $previousEndEpisode) {
                throw new LogicException(sprintf(
                    '[Season %d - %s] Start episode of the season is either the same or before the end of the previous season. WTF ?',
                    $season->no,
                    $season->title
                ));
            }
            
            $previousEndEpisode = $endEpisode;
        }
        
        return true;
    }
    
    /**
     * @return string[]
     */
    public function getDirectories()
    {
        if (is_array($this->directories)) {
            return $this->directories;
        }
        
        $directories = [];
        
        foreach ($this->data as $seasonNumber => $season) {
            $directories[$seasonNumber] = $this->getDirectory($season);
        }
        
        $this->directories = $directories;
        ksort($this->directories);
        
        return $this->directories;
    }
    
    /**
     * @param stdClass $season
     *
     * @return string
     */
    public function getDirectory(stdClass $season)
    {
        return sprintf('Season %02d - %s', $season->no, $season->title);
    }
    
    /**
     * @return array
     */
    public function getSeasons()
    {
        return $this->data;
    }
}
