#!/usr/bin/php -f
<?php
/**
 * System.spoje.net - Odeslání Upomínek
 *
 * @author     Vítězslav Dvořák <info@vitexsofware.cz>
 * @copyright  (G) 2017 Vitex Software
 */
define('EASE_APPNAME', 'Reminder');
define('MODULE_DIR', './notifiers');

require_once '../vendor/autoload.php';
$shared = new Ease\Shared();
$shared->loadConfig('../client.json', true);
$shared->loadConfig('../reminder.json', true);

$reminder = new \FlexiPeeHP\Reminder\Upominac();
$reminder->logBanner();

$reminder->processAllDebts();

