<?php

namespace Helper;


class DatabaseBuilder
{
    /** @var \Parable\ORM\Database */
    protected $database;

    /**
     * DatabaseBuilder constructor.
     * @param \Parable\ORM\Database $database
     */
    public function __construct(\Parable\ORM\Database $database)
    {
        $this->database = $database;
    }

    /**
     * @param string $fileName Full path and filename of the database to be created
     */
    public function createDatabaseFile($fileName)
    {
        // check if a database exists
//        $fileName = $dir . '/ml.sqlite';

        if (!file_exists($fileName)) {
            // create empty file for database
            file_put_contents($fileName, null);
        }

        $this->database->setType(\Parable\ORM\Database::TYPE_SQLITE);
        $this->database->setLocation($fileName);
    }

    public function buildStructure()
    {
        $this->database->query('drop table if exists `Root`')->execute();
        $this->database->query('drop table if exists `Artist`')->execute();
        $this->database->query('drop table if exists `SeeAlso`')->execute();
        $this->database->query('drop table if exists `Album`')->execute();
        $this->database->query('drop table if exists `Track`')->execute();

        $sql = 'create table `Root`
            (
                `id` integer primary key,
                `root` char(512),
                `updateDate` date,
                `status` integer,
                `customFields` text,
                `errors` text
            );';
        $statement = $this->database->query($sql);
        if ($statement) {
            $statement->execute();
        }

        $sql = 'create table `Artist`
            (
                `id` integer primary key,
                `root` char(512),
                `key` char(512),
                `altKey` char(512),
                `updateDate` date,
                `status` integer,
                `customFields` text,
                `errors` text
            );';
        $statement = $this->database->query($sql);
        if ($statement) {
            $statement->execute();
        }

        $sql = 'create table `SeeAlso`
            (
                `id` integer primary key,
                `root` char(512),
                `key1` char(512),
                `key2` char(512)
            );';
        $statement = $this->database->query($sql);
        if ($statement) {
            $statement->execute();
        }

        $sql = 'create table `Album`
            (
                `id` integer primary key,
                `root` char(512),
                `artistKey` char(512),
                `key` char(512),
                `altKey` char(512),
                `updateDate` date,
                `status` integer,
                `customFields` text,
                `errors` text
            );';
        $statement = $this->database->query($sql);
        if ($statement) {
            $statement->execute();
        }

        $sql = 'create table `Track`
            (
                `id` integer primary key,
                `root` char(512),
                `artistKey` char(512),
                `albumKey` char(512),
                `key` char(512),
                `altKey` char(512),
                `updateDate` date,
                `status` integer,
                `customFields` text,
                `errors` text
            );';
        $statement = $this->database->query($sql);
        if ($statement) {
            $statement->execute();
        }

    }
}
