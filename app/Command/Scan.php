<?php

namespace Command;

class Scan extends \Parable\Console\Command
{
    protected $name = 'scan';
    protected $description = 'Scan current folder, all artists';

    /** @var \Parable\ORM\Database */
    protected $database;
    /** @var \Parable\ORM\Repository */
    protected $sqliteMasterRepository;
    /** @var \Parable\ORM\Repository */
    protected $baseRepository;
    /** @var \Parable\ORM\Repository */
    protected $artistRepository;
    /** @var \Parable\ORM\Repository */
    protected $albumRepository;
    /** @var \Parable\ORM\Repository */
    protected $trackRepository;

    protected $baseDir;

    public function __construct(\Parable\ORM\Database $database, \Parable\ORM\Repository $repository)
    {
        $this->database = $database;
        $this->baseRepository = $repository;
        $this->sqliteMasterRepository = clone $repository;
        $this->artistRepository = clone $repository;
        $this->albumRepository = clone $repository;
        $this->trackRepository = clone $repository;

        $this->sqliteMasterRepository->setModel(\Parable\DI\Container::get(\Model\SqliteMaster::class));
        $this->artistRepository->setModel(\Parable\DI\Container::get(\Model\Artist::class));
        $this->albumRepository->setModel(\Parable\DI\Container::get(\Model\Album::class));
        $this->trackRepository->setModel(\Parable\DI\Container::get(\Model\Track::class));

        $this->addArgument('arg1', false, 'test');
        $this->addArgument('arg2', false);
        $this->addOption('a', false, false, false);
        $this->addOption('b', false, false, false);
    }

    public function run()
    {
        $this->baseDir = getcwd();

        $this->output->writeNotification('     ml - music list tool     ');

//        $this->output->writeln('<x><yellow>ml (music-list) - scan</yellow></x>');
//

//        $this->output->writeln("");
//        $this->output->writeln("test: <red>normal <b><inverse>test</inverse></b> normal</red>");

//        $this->output->writeln('<green>defined arguments: </green>' . json_encode($this->getArguments()));
//        $this->output->writeln('<green>defined options: </green>' . json_encode($this->getOptions()));
//        $this->output->writeln('<green>passed parameters: </green>' . json_encode($this->parameter->getParameters()));
//        $this->output->writeln("\n" . '<green>argument a: </green>' . json_encode($this->parameter->getArgument('a')));
//        $this->output->writeln('<green>argument b: </green>' . json_encode($this->parameter->getArgument('b')));
//        $this->output->writeln('<green>argument c: </green>' . json_encode($this->parameter->getArgument('c')));
//        $this->output->writeln('<green>option a: </green>' . json_encode($this->parameter->getOption('a')));
//        $this->output->writeln('<green>option b: </green>' . json_encode($this->parameter->getOption('b')));
//        $this->output->writeln('<green>option c: </green>' . json_encode($this->parameter->getOption('c')));

        $this->check();

        if ($this->parameter->getOption('rebuild')) {
            $this->rebuild();
        }

        $this->iterateArtists();
    }

    public function check()
    {
        // check if a database exists
        $fileName = $this->baseDir . '/ml.sqlite';

        if (!file_exists($fileName)) {
            // create empty file for database
            file_put_contents($fileName, null);
        }

        $this->database->setType(\Parable\ORM\Database::TYPE_SQLITE);
        $this->database->setLocation($fileName);

        // check database tables
//        $query = $this->sqliteMasterRepository->createQuery();
//        $artistTables = $this->sqliteMasterRepository->getByConditionSet(
//            $query->buildAndSet([
//                ['type', '=', 'table'],
//                ['name', '=', 'Artist'],
//            ])
//        );

//        $seeAlsoTables = $this->sqliteMasterRepository->getByConditionSet(
//            $query->buildAndSet([
//                ['type', '=', 'table'],
//                ['name', '=', 'SeeAlso'],
//            ])
//        );

//        $this->output->writeInfo('test query');
//        $this->output->writeln(print_r($artistTables, true));

        // check for ml.ini with information for the current Music List
        // TODO

    }

    protected function rebuild()
    {
        $this->database->query('drop table if exists `Artist`')->execute();
        $this->database->query('drop table if exists `SeeAlso`')->execute();
        $this->database->query('drop table if exists `Album`')->execute();
        $this->database->query('drop table if exists `Track`')->execute();

        $sql = 'create table Artist
            (
                `id` integer primary key,
                `key` char(512),
                `altKey` char(512),
                `updateDate` date,
                `status` integer,
                `customFields` text
            );';
        $statement = $this->database->query($sql);
        if ($statement) {
            $statement->execute();
        }

        $sql = 'create table SeeAlso
            (
                `id` integer primary key,
                `key1` char(512),
                `key2` char(512)
            );';
        $statement = $this->database->query($sql);
        if ($statement) {
            $statement->execute();
        }

        $sql = 'create table Album
            (
                `id` integer primary key,
                `artistKey` char(512),
                `key` char(512),
                `altKey` char(512),
                `updateDate` date,
                `status` integer,
                `customFields` text
            );';
        $statement = $this->database->query($sql);
        if ($statement) {
            $statement->execute();
        }

        $sql = 'create table Track
            (
                `id` integer primary key,
                `artistKey` char(512),
                `albumKey` char(512),
                `key` char(512),
                `altKey` char(512),
                `updateDate` date,
                `status` integer,
                `customFields` text
            );';
        $statement = $this->database->query($sql);
        if ($statement) {
            $statement->execute();
        }
    }

    public function iterateArtists()
    {
        /** @var \Model\Artist $artist */

        $this->output->writeLn('directory:');

        $this->database->setErrorMode(\PDO::ERRMODE_EXCEPTION);

        $sql = 'update Artist set status = 0';
        $statement = $this->database->query($sql);
        if ($statement) {
            $statement->execute();
        }

        // iterate over first-level directories
        $artistDirectories = [];
        foreach (new \DirectoryIterator($this->baseDir) as $fileInfo) {
            if (
                $fileInfo->isDot()
                || !$fileInfo->isDir()
                || substr($fileInfo->getFilename(), 0, 1) == '_'
                || substr($fileInfo->getFilename(), 0, 1) == '$'
            ) {
                continue;
            }
            $artistDirectories[] = $fileInfo->getPathname();
        }

        sort($artistDirectories);

        foreach ($artistDirectories as $artistDirectory) {
            $this->processArtist($artistDirectory);
        }

        $sql = 'delete from Artist where status = 0';
        $statement = $this->database->query($sql);
        if ($statement) {
            $statement->execute();
        }

    }

    protected function processArtist($dir)
    {
        /** @var \Model\Artist $artist */

        $displayDir = trim(str_replace($this->baseDir, '', $dir), '/');
        $this->output->write("<light-blue>{$displayDir}</light-blue>");

        $artist = $this->artistRepository->returnOne()->getByCondition('key', '=', $displayDir);
        if (!$artist) {
            $artist = $this->artistRepository->createModel();
        } else {
            $this->output->write(' - found, ');
        }

        $originalArtist = clone $artist;

        // default values taken from directory name
        $artist->key = $displayDir;
        $artist->altKey = $displayDir;

        // read artist.ini file
        $iniFile = $dir . '/artist.ini';
        if (file_exists($iniFile)) {
            $ini = parse_ini_file($iniFile);
            $artist->setFields($ini);

            $artist->altKey = $ini['artist.key'] ?? $displayDir;
        } else {
            // values taken from directory name if no artist.ini was found
            if (preg_match('/^([^[]*) *\[?([^]]*)\]?$/', $artist->key, $matches)) {
                $artist->setField('artist.name', trim($matches[1]));
                $artist->setField('artist.country', trim($matches[2]));
            }
        }

        $artist->status = 1;

        $isDifferent = $artist->isDifferent($originalArtist);

        if ($isDifferent) {
            $artist->setUpdateDate(new \DateTime());
        }

        $result = $artist->save();

        if ($isDifferent) {
            $this->output->writeln(' <yellow>added artist ID ' . $artist->id . '</yellow>');
            $this->processArtistSeeAlso($artist);
        } else {
            $this->output->writeln(' (ID = ' . $artist->id . ')');
        }

        $this->iterateAlbums($artist);

        // remove artist when no albums were found
        if (count($artist->getAlbums()) == 0) {
            $this->output->writeln(' <error>artist removed: no albums</error>');
            $artist->delete();
        }
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

    protected function iterateAlbums(\Model\Artist $artist)
    {
        /** @var \Model\Album $album */

        $pattern = $this->baseDir . '/' . $artist->key;
        $albumDirectories = [];

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

        foreach ($albumDirectories as $albumDirectory) {
//            $this->output->writeNotification("Album directory: {$fileInfo->getPathname()}");
            $this->processAlbum($artist, $albumDirectory);
        }

    }

    protected function processAlbum(\Model\Artist $artist, $dir)
    {
        $prefix = $this->baseDir . '/' . $artist->key . '/';
        $displayDir = substr($dir, strlen($prefix));

        $this->output->write(' * <light-blue>' . $displayDir . '</light-blue>');

        $isNew = false;

        /** @var \Model\Album $album */
        $album = $this->albumRepository->returnOne()->getByCondition('key', '=', $displayDir);
        if (!$album) {
            $isNew = true;
            $album = $this->albumRepository->createModel();
            $album->key = $displayDir;
        }

        $album->setArtist($artist);

        $originalAlbum = clone $album;

        // info form album.ini
        $iniFile = $dir . '/album.ini';
        if (file_exists($iniFile)) {
            $ini = parse_ini_file($iniFile);
            $album->setFields($ini);
        }

        $this->iterateTracks($album);

        // Album data set by album.ini (leading), generated from track tags. Fallback is the directory name.

        // if the album.ini or the tracks have not resulted in an artist, date, and title, get it from other data
        if (!$album->getField('album.artist')) {
            // if the track tags have not resulted in an Artist name, get it from the Artist directory name
            $album->setField('album.artist', $artist->getDisplayName());
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

        if ($isNew) {
            $this->output->write(' <yellow>added album ID ' . $album->id . '</yellow>');
        }

        if (count($album->getTracks()) == 0) {
            $this->output->write(' <error>no sound files found!</error>');
        } else {
            $this->output->write(' <info>added ' . count($album->getTracks()) . ' tracks</info>');
            if (!$album->getTracks()[0]->getField('artist')) {
                $this->output->write(' <error>no `artist` tag found!</error>');
            } elseif (!$album->getTracks()[0]->getField('date')) {
                $this->output->write(' <error>no `date` tag found!</error>');
            } elseif (!$album->getTracks()[0]->getField('album')) {
                $this->output->write(' <error>no `album` tag found!</error>');
            }
        }

        $this->output->writeln('');

        $artist->addAlbum($album);
    }

    protected function iterateTracks(\Model\Album $album)
    {
        /** @var \Model\Track $track */

        $trackFiles = [];
        $pattern = "{$this->baseDir}/{$album->getArtist()->key}/{$album->key}";
        foreach (new \DirectoryIterator($pattern) as $fileInfo) {
            if ($fileInfo->isDot() || !$fileInfo->isFile()) {
                continue;
            }
            $trackFiles[] = $fileInfo->getPathname();
        }

        sort($trackFiles);

        foreach ($trackFiles as $trackFile) {
            $this->processTrack($album, $trackFile);
        }
    }

    protected function processTrack(\Model\Album $album, $file)
    {
        /** @var \Model\Track $track */

        $artistKey = $album->getArtist()->key;
        $albumKey = $album->key;
        $dir = "{$this->baseDir}/{$album->getArtist()->key}/{$album->key}";
        $trackKey = trim(str_replace(trim($dir, '/') . '/', '', $file), '/');

        $cTime = filectime($file);
        $mTime = filemtime($file);
        $lastTime = max($cTime, $mTime);

        $track = $this->trackRepository->returnOne()->getByConditionSet(
            $this->trackRepository->buildAndSet([
                ['artistKey', '=', $artistKey],
                ['albumKey', '=', $albumKey],
                ['key', '=', $trackKey],
            ]));
        if (!$track) {
            $track = $this->trackRepository->createModel();
        }

        $id3 = new \getID3();

        try {
            $info = $id3->analyze($file);
            if (array_key_exists('error', $info)) {
//                $this->output->writeError($info['error']);
                return;
            }

            $process = false;
            $mime = $info['mime_type'] ?? '';
            if (substr($mime, 0, 5) == 'audio') {
                $process = true;
            }
            if (substr($mime, 0, 5) == 'video') {
                $process = true;
            }

            if (!$process) {
                return;
            }

            $originalTrack = clone $track;

            $track->artistKey = $artistKey;
            $track->albumKey = $albumKey;
            $track->key = $trackKey;

            $track->setField('seconds', $info['playtime_seconds']);
            $track->setField('duration', $info['playtime_string']);

            $track->setField('fileFormat', $info['fileformat']);
            $track->setField('lossless', $info['audio']['lossless']);
            $track->setField('channels', $info['audio']['channels']);
            $track->setField('sampleRate', $info['audio']['sample_rate']);
            $track->setField('bits', $info['audio']['bits_per_sample']);

            $track->setField('artist', $info['tags']['vorbiscomment']['artist'][0] ?? '');
            $track->setField('date', $info['tags']['vorbiscomment']['date'][0] ?? '');
            $track->setField('album', $info['tags']['vorbiscomment']['album'][0] ?? '');

            $track->setField('trackNumber', $info['tags']['vorbiscomment']['tracknumber'][0] ?? '');
            $track->setField('discNumber', $info['tags']['vorbiscomment']['discnumber'][0] ?? '');
            $track->setField('title', $info['tags']['vorbiscomment']['title'][0] ?? '');

            $album->addTrack($track);

            /**
             * copy data from first track to album
             */
            if (!($album->getField('album.artist') ?? '')) {
                $album->setField('album.artist', $track->getField('artist'));
            }
            if (!($album->getField('album.date') ?? '')) {
                $album->setField('album.date', $track->getField('date'));
            }
            if (!($album->getField('album.date') ?? '')) {
                $album->setField('album.date', $track->getField('date'));
            }
            if (!($album->getField('album.title') ?? '')) {
                $album->setField('album.title', $track->getField('album'));
            }

            $seconds = $album->getField('album.seconds') ?? 0;
            $seconds += ($track->getField('seconds') ?? 0);
            $album->setField('album.seconds', $seconds);

            $track->setUpdateDate(new \DateTime());
            $track->save();

            $album->addTrack($track);

            $dateTime1 = new \DateTime('2000-01-01 00:00:00');
            $dateTime2 = clone $dateTime1;
            $dateTime2->modify('+ ' . round($seconds) . ' seconds');
            $interval = $dateTime2->diff($dateTime1);
            $hours = (int)$interval->format('%h') + (int)($interval->days * 60 * 60 * 24);
            $duration = '';
            if ($hours > 0) {
                $duration = (string) $hours . ':';
            }
            $duration .= $interval->format('%I:%S');
            $album->setField('album.duration', $duration);
            $album->setField('album.h', $hours);
            $album->setField('album.m', $interval->format('%i'));
            $album->setField('album.s', $interval->format('%s'));
        } catch (\Exception $e) {
            $this->output->writeError($e->getMessage());
        }
    }

}
