<?php

/**
 * @file
 * Dotenv env.properties file load.
 *
 * This file is included very early. See autoload.files in composer.json and
 * https://getcomposer.org/doc/04-schema.md#files.
 */

use Dotenv\Dotenv;

/**
 * Load any env.properties file. See /env.properties.example.
 */
$dotenv = Dotenv::createImmutable(__DIR__, 'env.properties');
$dotenv->safeLoad();
