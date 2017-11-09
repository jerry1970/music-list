<?php

namespace Model;

class Album extends \Parable\ORM\Model
{
    /** @var string */
    protected $tableName = 'Album';

    /** @var string */
    protected $tableKey = 'id';

    /** @var Artist */
    protected $artist;

    /** @var string */
    public $artistKey;

    /** @var string */
    public $key;

    /** @var string */
    public $altKey;

    /** @var int */
    public $status;

    /** @var string */
    public $updateDate;

    /** @var Track[] */
    protected $tracks = [];

    /** @var string[] */
    protected $errors = [];

    use CustomFields;

    /** @var array */
    protected $exportable = ['key', 'updateDate', 'customFields'];

    /**
     * @param Artist $artist
     * @return $this
     */
    public function setArtist(Artist $artist)
    {
        $this->artist = $artist;
        $this->artistKey = $artist->key;
        return $this;
    }

    /**
     * @return Artist
     */
    public function getArtist()
    {
        return $this->artist;
    }

    public function getNotes()
    {
        $notes = $this->getField('notes');

        if ($notes && is_array($notes)) {
            return $notes;
        }

        return [];
    }

    public function setUpdateDate(\DateTime $dt)
    {
        $this->updateDate = $dt->format('Y-m-d H:i:s');
    }

    public function getUpdateDate()
    {
        return new \DateTime($this->updateDate);
    }

    public function addTrack(Track $track)
    {
        $this->tracks[] = $track;
        return $this;
    }

    public function getTracks()
    {
        return $this->tracks;
    }

    public function addError($error)
    {
        $this->errors[] = $error;
        return $this;
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function isDifferent(Album $originalAlbum)
    {
        if ($originalAlbum->artistKey != $this->artistKey) {
            return true;
        }
        if ($originalAlbum->key != $this->key) {
            return true;
        }
        if ($originalAlbum->altKey != $this->altKey) {
            return true;
        }
        if ($originalAlbum->customFields != $this->customFields) {
            return true;
        }
        return false;
    }

}
