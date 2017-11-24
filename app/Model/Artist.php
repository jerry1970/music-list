<?php
/**
 * Artist model
 *
 * The artist.ini file can have the following keys:
 * artist.key = an alternative key (directory name is the main key)
 * artist.country = country of origin
 * artist.name = if the spelling is different than the directory name
 * artist.notes[] = zero or more notes
 *
 * When no artist.ini file is present, the directory name is used as the name,
 * with an optional country in square brackets. E.g.: Magnum [UK].
 */

namespace Model;

class Artist extends \Parable\ORM\Model
{
    /** @var string */
    protected $tableName = 'Artist';

    /** @var string */
    protected $tableKey = 'id';

    /** @var Root */
    protected $root;

    /** @var string */
    public $rootKey;

    /** @var string */
    public $key;

    /** @var string */
    public $altKey;

    /** @var int */
    public $status;

    /** @var string */
    public $updateDate;

    /** @var Album[] */
    protected $albums;

    /** @var boolean */
    protected $isNew = false;

    use CustomFields;

    use ErrorField;

    /** @var array */
    protected $exportable = ['key', 'altKey', 'updateDate', 'customFields'];

    /**
     * @return Root
     */
    public function getRoot(): Root
    {
        return $this->root;
    }

    /**
     * @param Root $root
     * @return $this
     */
    public function setRoot(Root $root)
    {
        $this->root = $root;
        return $this;
    }

    public function getDisplayName()
    {
        // check fields from INI file
        $displayName = $this->getField('artist.name');
        if ($displayName) {
            return $displayName;
        }

        // fallback to key, but strip country name
        $displayName = $this->key;
        if (preg_match('/^(.*)\s*\[.+\]\s*$/', $this->key, $matches)) {
            $displayName = $matches[1];
        }

        return $displayName;
    }

    public function getCountry()
    {
        // check fields from INI file
        $country = $this->getField('country');
        if ($country) {
            return $country;
        }

        // fallback to key with a country name is given
        if (preg_match('/^\[(.+)\]\s*$/', $this->key, $matches)) {
            return $matches[1];
        }

        return '';
    }

    public function getNotes()
    {
        $notes = $this->getField('notes');

        if ($notes && is_array($notes)) {
            return $notes;
        }

        return [];
    }

    public function addAlbum(Album $album)
    {
        $this->albums[] = $album;
        return $this;
    }

    public function getAlbums()
    {
        return $this->albums;
    }

    public function setUpdateDate(\DateTime $dt)
    {
        $this->updateDate = $dt->format('Y-m-d H:i:s');
    }

    public function getUpdateDate()
    {
        return new \DateTime($this->updateDate);
    }

    public function isDifferent(Artist $originalArtist)
    {
        if ($originalArtist->key != $this->key) {
            return true;
        }
        if ($originalArtist->altKey != $this->altKey) {
            return true;
        }
        if ($originalArtist->customFields != $this->customFields) {
            return true;
        }
        return false;
    }

    /**
     * @return bool
     */
    public function isNew(): bool
    {
        return $this->isNew;
    }

    /**
     * @param bool $isNew
     * @return Artist
     */
    public function setIsNew(bool $isNew): Artist
    {
        $this->isNew = $isNew;
        return $this;
    }

}
