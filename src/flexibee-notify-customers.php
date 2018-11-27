#!/usr/bin/php -f
<?php
/**
 * System.spoje.net - Odeslání Upomínek
 *
 * @author     Vítězslav Dvořák <info@vitexsofware.cz>
 * @copyright  (G) 2018 Vitex Software
 */
define('EASE_APPNAME', 'ClientsNotifier');
define('MODULES', './notifiers');

require_once '../vendor/autoload.php';
$shared = new Ease\Shared();
$shared->loadConfig('../client.json', true);
$shared->loadConfig('../reminder.json', true);

$reminder = new \FlexiPeeHP\Reminder\Upominac();
$reminder->logBanner(constant('EASE_APPNAME'));
$allDebts = $reminder->getAllDebts();


$clientsToNotify = [];
foreach ($allDebts as $kod => $debtData) {
    $clientsToNotify[$debtData['firma']][$kod] = $debtData;
}


$counter = 0;
foreach ($clientsToNotify as $firma => $debts) {
    $reminder->addStatusMessage(sprintf(_('(%d / %d) %s '), $counter++,
            count($clientsToNotify),
            isset(current($debts)['firma@showAs']) ? current($debts)['firma@showAs']
                    : current($debts)['firma'] ), 'debug');
    $reminder->customer->adresar->loadFromFlexiBee($firma);
    $reminder->notify(0, $debts, constant('MODULES').'/ByEmail.php');
}