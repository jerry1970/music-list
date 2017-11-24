<?php

namespace Config;

class App implements
    \Parable\Framework\Interfaces\Config
{
    /**
     * @return array
     */
    public function get()
    {
        return [
            "parable" => [
                "app" => [
                    "title" => "Parable",
                    "homeDir" => "public",
                ],
                "session" => [
                    "autoEnable" => true,
                ],
                "database" => [
                    "type" => \Parable\ORM\Database::TYPE_SQLITE,
                    "location" => "",
                    "username" => "",
                    "password" => "",
                    "database" => "",
                ],
                "configs" => [
                    \Config\Custom::class
                ],
                "commands" => [
                    \Command\Scan::class,
                ],
                "inits" => [
                    \Init\Example::class,
                ],
                "routes" => [
                    \Routing\App::class,
                ],
            ],
        ];
    }
}
