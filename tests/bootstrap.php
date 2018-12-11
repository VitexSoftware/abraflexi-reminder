<?php
/**
 * FlexiPeeHP-Bricks - Unit Test bootstrap
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 * @copyright (c) 2018, Vítězslav Dvořák
 */
if (file_exists('../vendor/autoload.php')) {
    require_once '../vendor/autoload.php'; //Test Run
    \Ease\Shared::instanced()->loadConfig('../client.json');
    \Ease\Shared::instanced()->loadConfig('../reminder.json');    
} else {
    require_once 'vendor/autoload.php'; //Create Test
    \Ease\Shared::instanced()->loadConfig('client.json');
    \Ease\Shared::instanced()->loadConfig('reminder.json');
}

