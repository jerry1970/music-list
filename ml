#!/usr/bin/env php
<?php

/**
 * ml = music-list
 */

ini_set('display_errors', '1');

/** @var \Parable\Console\App $app */
$app = require_once(__DIR__ . '/vendor/devvoh/parable/src/Framework/Bootstrap.php');

$app->setName('ml (music-list)');

// Always add Help
$app->addCommand(\Parable\DI\Container::get(\Parable\Console\Command\Help::class));

// Attempt to load commands set by the user
if (file_exists($path->getDir('app'))) {
    /** @var \Parable\Framework\Config $config */
    $config = \Parable\DI\Container::get(\Parable\Framework\Config::class);
    $config->load();

    if ($config->get('parable.commands')) {
        // We don't try/catch because the dev shouldn't add non-existing classes.
        foreach ($config->get('parable.commands') as $commandClassName) {
            $app->addCommand(\Parable\DI\Container::get($commandClassName));
        }
    }
}

$app->setDefaultCommand('help');
$app->run();
