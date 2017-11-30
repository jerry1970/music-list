<?php

namespace Command;

use Ayesh\PHP_Timer\Timer;
use Parable\ORM\Repository;

class Scan extends \Parable\Console\Command
{
    protected $name = 'scan';
    protected $description = 'Scan current folder, all artists';

    /** @var \Parable\ORM\Database */
    protected $database;
    /** @var \Parable\ORM\Repository */
    protected $sqliteMasterRepository;
    /** @var \Parable\ORM\Repository */
    protected $artistRepository;
    /** @var \Parable\ORM\Repository */
    protected $albumRepository;
    /** @var \Parable\ORM\Repository */
    protected $trackRepository;

    /** @var \Helper\Artist */
    protected $artistHelper;
    /** @var \Helper\Album */
    protected $albumHelper;
    /** @var \Helper\Track */
    protected $trackHelper;

    /** @var string */
    protected $baseDir;

    /** @var \Model\Root */
    protected $root;

    public function __construct(
        \Parable\ORM\Database $database,
        \Parable\ORM\Repository $repository,
        \Model\Root $root,
        \Helper\Artist $artistHelper,
        \Helper\Album $albumHelper,
        \Helper\Track $trackHelper
    ) {
        $this->database = $database;

        $this->sqliteMasterRepository = Repository::createInstanceForModelName(\Model\SqliteMaster::class);
        $this->artistRepository = Repository::createInstanceForModelName(\Model\Artist::class);
        $this->albumRepository = Repository::createInstanceForModelName(\Model\Album::class);
        $this->trackRepository = Repository::createInstanceForModelName(\Model\Track::class);

        $this->root = $root;

        $this->artistHelper = $artistHelper;
        $this->albumHelper = $albumHelper;
        $this->trackHelper = $trackHelper;

        $this->artistHelper->setRoot($root);
        $this->albumHelper->setRoot($root);

        $this->addArgument('arg1', false, 'test');
        $this->addArgument('arg2', false);
        $this->addOption('max', false, false, 0);

        // option --db for location of the music list root, by default the working directory
        $this->addOption('ml', false, false, '.');

        // option --db for location of the database, by default the working directory
        $this->addOption('db', false, false, '.');
    }

    protected function checkRoot()
    {
        $this->baseDir = getcwd();
        $root = realpath($this->baseDir . '/' . $this->parameter->getOption('ml'));
        if (!file_exists($root)) {
            $this->output->writeError("Path {$root} does not exists!");
            return false;
        }
        if (!is_dir($root)) {
            $this->output->writeError("Path {$root} is not a directory!");
            return false;
        }
        $this->root->key = $root;
        $this->output->writeln("Music List Root: <yellow>{$root}</yellow>");
        return true;
    }

    protected function checkDatabase()
    {
        $dbLocation = $this->baseDir . DIRECTORY_SEPARATOR . $this->parameter->getOption('db');
        if (is_dir($dbLocation)) {
            $dbLocation .= '/ml.sqlite';
        } else {
            if (!file_exists(dirname($dbLocation)) || !is_dir(dirname($dbLocation))) {
                $this->output->writeError("Database path " . dirname($dbLocation) . " is not a directory!");
                return false;
            }
        }
        $this->output->writeln("Database: <yellow>{$dbLocation}</yellow>");

        /** @var \Helper\DatabaseBuilder $builder */
        $builder = \Parable\DI\Container::get(\Helper\DatabaseBuilder::class);
        $builder->createDatabaseFile($dbLocation);

        // rebuild database structure if required
        if ($this->parameter->getOption('rebuild')) {
            $builder->buildStructure();
        }

        return true;
    }

    public function run()
    {
        $this->output->writeNotification(str_pad('ml - music list', 80, ' ', STR_PAD_BOTH));

        Timer::start('run');

        /**
         * check the --ml options
         */
        if (!$this->checkRoot()) {
            exit(1);
        }

        /**
         * check the --db option: location of the database
         */
        if (!$this->checkDatabase()) {
            exit(1);
        }

        /**
         * check for ml.ini
         */
        // TODO

        $this->iterateArtists();

        // remove all records that have not been updated

        Timer::stop('run');
        $this->output->writeln('<green>Finished, took ' . Timer::read('run', Timer::FORMAT_MILLISECONDS) . ' ms</green>');
        $timerKeys = [
            'artist-directories',
            'artist-from-directory',
            'artist-process',
            'album-directories',
            'album-from-directory',
            'album-process',
        ];
        foreach ($timerKeys as $key) {
            try {
                $this->output->writeln('  <green>' . $key . ': '
                    . Timer::read($key, Timer::FORMAT_MILLISECONDS) . ' ms'
                    . '</green>');
            } catch (\Exception $e) {
                // do nothing, just skip
            }
        }
    }

    protected function iterateArtists()
    {
        /** @var \Model\Artist $artist */

        $filterText = $this->parameter->getOption('a');
        $filterInitial = $this->parameter->getOption('i');
        if ($filterInitial) {
            // --i takes precedence over --a
            $filterText = substr($filterInitial, 0, 1) . '*';
        }

        /**
         * reset status
         */

        $this->artistHelper->resetStatus($filterText);

        /**
         * section: get artist directories
         */

        Timer::start('artist-directories');

        $this->artistHelper->setRoot($this->root);
        $artistDirectories = $this->artistHelper->getDirectories($this->root, $filterText);

        Timer::stop('artist-directories');



        $artistCount = 0;
        foreach ($artistDirectories as $dir) {
            $artistCount++;

            // check for maximum number of folders; used for testing
            if ($this->parameter->getOption('max') && $artistCount > $this->parameter->getOption('max')) {
                return;
            }

            Timer::start('artist-from-directory');
            $artist = $this->artistHelper->getArtistFromDirectory($dir);
            Timer::stop('artist-from-directory');

            $this->output->write("<light-blue>{$artist->getDisplayName()}</light-blue>");

            Timer::start('artist-process');
            $artist = $this->artistHelper->processArtist($artist);
            Timer::stop('artist-process');

            if ($artist->isNew()) {
                $this->output->write(" <light-yellow>ID {$artist->id}</light-yellow>");
            } else {
                $this->output->write(" ID {$artist->id}");
            }

            $this->output->writeln("");

            // for the current artist, iterate over all album directories
            $this->iterateAlbums($artist);
        }
    }

    protected function iterateAlbums(\Model\Artist $artist)
    {
        /** @var \Model\Album $album */

        Timer::start('album-directories');

        $this->albumHelper->setArtist($artist);

        $albumDirectories = $this->albumHelper->getDirectories($this->root, $artist);
        Timer::stop('album-directories');

        foreach ($albumDirectories as $albumDirectory) {
            Timer::start('album-from-directory');
            $album = $this->albumHelper->getAlbumFromDirectory($albumDirectory);
            Timer::stop('album-from-directory');

            //$this->output->writeNotification("Album directory: {$fileInfo->getPathname()}");
            Timer::start('album-process');
            $this->albumHelper->processAlbum($album);
            Timer::stop('album-process');

            // read the tracks
            $this->iterateTracks($album);

            // post process for album after tracks have been read
            $this->albumHelper->postProcessAlbum($album);

            $this->output->write(" * <light-blue>{$album->key}</light-blue>");
            if ($album->isNew()) {
                $this->output->write(' <light-yellow>ID ' . $album->id . '</light-yellow>');
            } else {
                $this->output->write(' ID ' . $album->id);
            }
            $this->output->writeln('');
        }
    }

    protected function iterateTracks(\Model\Album $album)
    {
        /** @var \Model\Track $track */

        $trackFiles = $this->trackHelper->getFiles($this->root, $album);
        foreach ($trackFiles as $trackFile) {
            $this->trackHelper->processTrack($album, $trackFile);
        }
    }

}
