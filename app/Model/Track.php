<?php

namespace Model;

class Track extends \Parable\ORM\Model
{
    /** @var string */
    protected $tableName = 'Track';

    /** @var string */
    protected $tableKey = 'id';

    /** @var Album */
    protected $album;

    /** @var string */
    public $artistKey;

    /** @var string */
    public $albumKey;

    /** @var string */
    public $key;

    /** @var int */
    public $status;

    /** @var string */
    public $updateDate;

    use CustomFields;

    /** @var array */
    protected $exportable = ['artistKey', 'albumKey', 'key', 'updateDate', 'customFields'];

    /**
     * @return Album
     */
    public function getAlbum(): Album
    {
        return $this->album;
    }

    /**
     * @param Album $album
     * @return $this
     */
    public function setAlbum(Album $album)
    {
        $this->album = $album;
        $this->albumKey = $album->key;
        $this->artistKey = $album->getArtist()->key;
        return $this;
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

}
