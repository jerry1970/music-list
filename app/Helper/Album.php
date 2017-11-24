<?php

namespace Helper;

class Album
{
    //<editor-fold desc="properties">

    /** @var \Parable\ORM\Database */
    protected $database;

    /** @var \Parable\ORM\Repository */
    protected $albumRepository;

    /** @var \Model\Root */
    protected $root;

    /** @var \Model\Artist */
    protected $artist;

    //</editor-fold>

    //<editor-fold desc="constructor">

    /**
     * Artist constructor.
     * @param \Parable\ORM\Database $database
     */
    public function __construct(\Parable\ORM\Database $database, \Parable\ORM\Repository $repository)
    {
        $this->database = $database;
        /**
         * TODO: find different way of using shared instance of repository
         * Maybe add query to repo that has model, so single repo can return multiple queries
         */
        $this->albumRepository = clone $repository;
        $this->albumRepository->setModel(\Parable\DI\Container::create(\Model\Album::class));
    }

    //</editor-fold>


    //<editor-fold desc="getters / setters">
    /**
     * @return \Model\Root
     */
    public function getRoot(): \Model\Root
    {
        return $this->root;
    }

    /**
     * @param \Model\Root $root
     * @return Album
     */
    public function setRoot(\Model\Root $root): Album
    {
        $this->root = $root;
        return $this;
    }

    /**
     * @return \Model\Artist
     */
    public function getArtist(): \Model\Artist
    {
        return $this->artist;
    }

    /**
     * @param \Model\Artist $artist
     * @return Album
     */
    public function setArtist(\Model\Artist $artist): Album
    {
        $this->artist = $artist;
        return $this;
    }
    //</editor-fold>

    public function getDirectories(\Model\Root $root, \Model\Artist $artist)
    {
        $this->root = $root;
        $this->artist = $artist;

        $albumDirectories = [];
        $pattern = $root->key . '/' . $artist->key;

        foreach (new \DirectoryIterator($pattern) as $fileInfo) {
            if (
                $fileInfo->isDot()
                || !$fileInfo->isDir()
                || substr($fileInfo->getFilename(), 0, 1) == '$'
            ) {
                continue;
            }
            $albumDirectories[] = $fileInfo->getPathname();
        }

        sort($albumDirectories);

        return $albumDirectories;
    }

    public function getAlbumFromDirectory($dir)
    {
        $prefix = $this->root->key . '/' . $this->artist->key . '/';
        $displayDir = substr($dir, strlen($prefix));

        /** @var \Model\Album $album */
        $album = $this->albumRepository->returnOne()->getByCondition('key', '=', $displayDir);
        if (!$album) {
            $album = $this->albumRepository->createModel();
            $album->key = $displayDir;
            $album->setIsNew(true);
        }

        $album->setArtist($this->artist);

//        $originalAlbum = clone $album;

        // info form album.ini
        $iniFile = $dir . '/album.ini';
        if (file_exists($iniFile)) {
            $ini = parse_ini_file($iniFile);
            $album->setFields($ini);
        }

        return $album;
    }

    public function processAlbum(\Model\Album $album)
    {
        // read artist.ini file
        $iniFile = $album->key . '/album.ini';
        if (file_exists($iniFile)) {
            $ini = parse_ini_file($iniFile);
            $album->setFields($ini);
        } else {
            // values taken from directory name if no artist.ini was found
            if (preg_match('/^([^[]*) *\[?([^]]*)\]?$/', $album->key, $matches)) {
                $album->setField('artist.name', trim($matches[1]));
                $album->setField('artist.country', trim($matches[2]));
            }
        }

        $album->status = 1;

        $result = $album->save();
    }

    public function postProcessAlbum(\Model\Album $album)
    {
        // Album data set by album.ini (leading), generated from track tags. Fallback is the directory name.

        // if the album.ini or the tracks have not resulted in an artist, date, and title, get it from other data
        if (!$album->getField('album.artist')) {
            // if the track tags have not resulted in an Artist name, get it from the Artist directory name
            $album->setField('album.artist', $album->getArtist()->getDisplayName());
        }
        if (!$album->getField('album.title') || !$album->getField('album.date')) {
            // if the track tags did not supply album title or date, parse the Album directory name
            if (preg_match('/^(\d{4}.?|\d{4}-\d{2}.?|\d{4}-\d{2}-\d{2}.?),\s+(.*)$/', $album->key, $matches)) {
                $date = $matches[1];
                $title = $matches[2];
                $options = [];
                if (preg_match_all('/([^[]*) (?:\[([^]]*)\])+/', $title, $matches)) {
                    $title = $matches[1][0];
                    $options = $matches[2]; // all options in "Something [A] [B]"
                }
                if (!$album->getField('album.title')) {
                    $album->setField('album.title', $title);
                }
                if (!$album->getField('album.date')) {
                    $album->setField('album.date', $date);
                }
                foreach ($options as $option) {
                    switch (strtoupper($option)) {
                        case 'NFT':
                            $album->setField('album.notForTrade', true);
                            break;
                        case 'HIDDEN':
                            $album->setField('album.hidden', true);
                            break;
                        default:
                            $album->setField('album.' . $option, true);
                            break;
                    }
                }
            }
        }

        $album->save();
    }


}
