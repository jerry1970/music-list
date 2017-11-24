<?php

namespace Helper;

class Artist
{
    //<editor-fold desc="properties">

    /** @var \Parable\ORM\Database */
    protected $database;

    /** @var \Parable\ORM\Repository */
    protected $artistRepository;

    /** @var \Model\Root */
    protected $root;

    /** @var int */
    protected $artistCount;

    //</editor-fold>

    //<editor-fold desc="constructor">

    /**
     * Artist constructor.
     * @param \Parable\ORM\Database $database
     * @param \Parable\ORM\Repository $repository
     */
    public function __construct(\Parable\ORM\Database $database, \Parable\ORM\Repository $repository)
    {
        $this->database = $database;
        $this->artistRepository = clone $repository;
        $this->artistRepository->setModel(\Parable\DI\Container::create(\Model\Artist::class));
    }

    //</editor-fold>

    public function setRoot(\Model\Root $root)
    {
        $this->root = $root;
    }

    /**
     * @return int
     */
    public function getArtistCount(): int
    {
        return $this->artistCount;
    }

    /**
     * Resets the status of all artists or filtered by the given filter test
     *
     * @param string $filterText
     * @return $this
     */
    public function resetStatus($filterText = '')
    {
        $hasFilter = ($filterText != '');
        $hasWildcardFilter = (strpos($filterText, '*') !== false);
        if ($hasWildcardFilter) {
            $filterText = str_replace('*', '%', $filterText);
        }

        $sql = 'update `Artist` set status = 0 where `root` = :root';
        $data = [
            ':root' => $this->root->key,
        ];
        if ($hasWildcardFilter) {
            $sql = 'update `Artist` set status = 0 where `root` = :root and `key` like :filter';
            $data[':filter'] = $filterText;
        } elseif ($hasFilter) {
            $sql = 'update `Artist` set status = 0 where `root` = :root and `key` = :filter';
            $data[':filter'] = $filterText;
        }
        $statement = $this->database->query($sql);
        if ($statement) {
            $statement->execute($data);
        }

        return $this;
    }

    /**
     * Gets all Artist directories in the given root, sorted alphabetically, optionally filtered
     *
     * @param \Model\Root $root
     * @param string $filterText Full directory name to filter on or string with wildcard
     * @return array
     */
    public function getDirectories(\Model\Root $root, $filterText = '')
    {
        $hasFilter = ($filterText != '');
        $hasWildcardFilter = (strpos($filterText, '*') !== false);
        if ($hasWildcardFilter) {
            $filterText = '/' . str_replace('*', '.*', $filterText) . '/';
        }

        $artistDirectories = [];
        foreach (new \DirectoryIterator($root->key) as $fileInfo) {
            if (
                $fileInfo->isDot()
                || !$fileInfo->isDir()
                || substr($fileInfo->getFilename(), 0, 1) == '.'
                || substr($fileInfo->getFilename(), 0, 1) == '_'
                || substr($fileInfo->getFilename(), 0, 1) == '$'
            ) {
                continue;
            }

            if ($hasWildcardFilter && !preg_match($filterText, $fileInfo->getPathname())) {
                continue;
            } elseif ($hasFilter && $fileInfo->getPathname() !== $filterText) {
                continue;
            }

            $artistDirectories[] = $fileInfo->getPathname();
        }
        sort($artistDirectories);

        return $artistDirectories;
    }

//    public function OLDiterateDirectories($artistDirectories)
//    {
//        /** @var \Model\Artist $artist */
//
//        $this->database->setErrorMode(\PDO::ERRMODE_EXCEPTION);

        // set all artists to 0
//        $sql = 'update Artist set status = 0';
//        $statement = $this->database->query($sql);
//        if ($statement) {
//            $statement->execute();
//        }

        // process all artist directories
//        foreach ($artistDirectories as $artistDirectory) {
//            $this->processDirectory($artistDirectory);
//        }

        // delete artist records that still have a status = 0
//        $sql = 'delete from Artist where status = 0';
//        $statement = $this->database->query($sql);
//        if ($statement) {
//            $statement->execute();
//        }
//    }


    public function getArtistFromDirectory($dir)
    {
        /** @var \Model\Artist $artist */

        // generate artist key from full path
        $displayDir = trim(str_replace($this->root->key, '', $dir), '/');

//        $query = $this->artistRepository->createQuery();
//        $artist = $this->artistRepository->returnOne()->getByConditionSet(
//            $query->buildAndSet([
//                ['root', '=', $this->root->key],
//                ['key', '=', $displayDir],
//            ])
//        );
//        if (!$artist) {
//            $artist = $this->artistRepository->createModel();
//            $artist->setIsNew(true);
//        }

        $artist = $this->artistRepository->createModel();

//        $originalArtist = clone $artist;

        // default values taken from directory name
        $artist->key = $displayDir;
        $artist->altKey = $displayDir;

        // get properties from artist key
        $defaultName = $artist->key;
        $defaultCountry = '';
        if (preg_match('/^([^[]*) *\[?([^]]*)\]?$/', $artist->key, $matches)) {
            $defaultName = trim($matches[1]);
            $defaultCountry = trim($matches[2]);
        }

        // get properties from artist.ini file
        $iniFile = $dir . '/artist.ini';
        if (file_exists($iniFile)) {
            $ini = parse_ini_file($iniFile);
            // store complete ini file
            $artist->setFields($ini);
            // set model properties from ini
            $artist->altKey = $ini['artist.key'] ?? $displayDir;
        }

        // check if default values for properties have to be set
        if (!$artist->getField('artist.name')) {
            $artist->setField('artist.name', $defaultName);
        }
        if (!$artist->getField('artist.country')) {
            $artist->setField('artist.country', $defaultCountry);
        }

        $artist->status = 1;
        $this->artistCount++;

//        $isDifferent = $artist->isDifferent($originalArtist);
//        if ($isDifferent) {
//            $artist->setUpdateDate(new \DateTime());
//        }

        $artist->save();

//        if ($isDifferent) {
//            $this->processArtistSeeAlso($artist);
//        } else {
//        }

        return $artist;
    }

    /**
     * @param \Model\Artist $artist
     * @return \Model\Artist
     */
    public function processArtist(\Model\Artist $artist): \Model\Artist
    {
        /** @var \Model\Artist $dbArtist */

        $query = $this->artistRepository->createQuery();
        $dbArtist = $this->artistRepository->returnOne()->getByConditionSet(
            $query->buildAndSet([
                ['root', '=', $this->root->key],
                ['key', '=', $artist->key],
            ])
        );
        if (!$dbArtist) {
            $artist->setIsNew(true);
        } else {
            $artist->id = $dbArtist->id;
            if ($artist->isDifferent($dbArtist)) {
                $artist->setUpdateDate(new \DateTime());
            }
        }
        $artist->status = 1;

        $artist->save();

        return $artist;
    }

    protected function processArtistSeeAlso(\Model\Artist $artist)
    {
        // remove current SeeAlso records
        $sql = 'delete from SeeAlso where key1 = :key1';
        $this->database->query($sql)->execute([':key1' => $artist->key]);

        $seeAlsoArray = $artist->getField('artist.seeAlso');

        if ($seeAlsoArray && is_array($seeAlsoArray) && $artist->key) {
            $sql = 'insert into SeeAlso (key1, key2) values (:key1, :key2)';
            $query = $this->database->prepare($sql);
            foreach ($seeAlsoArray as $seeAlso) {
                if ($seeAlso) {
                    $query->execute([
                        ':key1' => $artist->key,
                        ':key2' => $seeAlso,
                    ]);
                }
            }
        }
    }


}
