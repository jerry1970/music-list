<?php

namespace Helper;

class Track
{
    //<editor-fold desc="properties">

    /** @var \Parable\ORM\Database */
    protected $database;

    /** @var \Parable\ORM\Repository */
    protected $trackRepository;

    /** @var \Model\Root */
    protected $root;

    /** @var \Model\Artist */
    protected $artist;

    /** @var \Model\Album */
    protected $album;

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
        $this->trackRepository = clone $repository;
        $this->trackRepository->setModel(\Parable\DI\Container::create(\Model\Track::class));
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
     * @return Track
     */
    public function setRoot(\Model\Root $root): Track
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
     * @return Track
     */
    public function setArtist(\Model\Artist $artist): Track
    {
        $this->artist = $artist;
        return $this;
    }

    /**
     * @return \Model\Album
     */
    public function getAlbum(): \Model\Album
    {
        return $this->album;
    }

    /**
     * @param \Model\Album $album
     * @return Track
     */
    public function setAlbum(\Model\Album $album): Track
    {
        $this->album = $album;
        return $this;
    }

    //</editor-fold>

    public function getFiles(\Model\Root $root, \Model\Album $album)
    {
        $this->root = $root;
        $this->artist = $album->getArtist();
        $this->album = $album;

        $trackFiles = [];
        $pattern = $root->key . '/' . $album->artistKey . '/' . $album->key;
        foreach (new \DirectoryIterator($pattern) as $fileInfo) {
            if (
                $fileInfo->isDot()
                || !$fileInfo->isFile()
            ) {
                continue;
            }
            $files[] = $fileInfo->getPathname();
            $trackFiles[] = $fileInfo->getPathname();
        }

        sort($trackFiles);

        return $trackFiles;
    }

    public function processTrack(\Model\Album $album, $file)
    {
        /** @var \Model\Track $track */

        $artistKey = $album->getArtist()->key;
        $albumKey = $album->key;
        $dir = "{$this->root->key}/{$album->getArtist()->key}/{$album->key}";
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

        $info = $id3->analyze($file);
        if (array_key_exists('error', $info)) {
//            throw new \Exception('Could not read tags! ' . print_r($info['error'], true));
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
        $track->setField('lossless', $info['audio']['lossless'] ?? false);
        $track->setField('channels', $info['audio']['channels'] ?? null);
        $track->setField('sampleRate', $info['audio']['sample_rate'] ?? null);
        $track->setField('bits', $info['audio']['bits_per_sample'] ?? null);

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
    }

}
