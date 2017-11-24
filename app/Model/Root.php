<?php

namespace Model;

class Root extends \Parable\ORM\Model
{
    /** @var string */
    protected $tableName = 'Root';

    /** @var string */
    protected $tableKey = 'id';

    /** @var string */
    public $key;

    /** @var Artist[] */
    protected $artists;

    use CustomFields;

    use ErrorField;

    public function addArtist(Artist $artist)
    {
        $this->artists[] = $artist;
    }

    public function getArtists()
    {
        return $this->artists;
    }

}
