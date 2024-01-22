<?php
/**
 * AbraFlexi-Reminder - Unit Test bootstrap
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 * @copyright (c) 2018, Vítězslav Dvořák
 */
if (file_exists('../vendor/autoload.php')) {
    require_once '../vendor/autoload.php'; //Test Run
    \Ease\Shared::instanced()->loadConfig('../.env.example');
} else {
    require_once 'vendor/autoload.php'; //Create Test
    \Ease\Shared::instanced()->loadConfig('.env.example');
}
