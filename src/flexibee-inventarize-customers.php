#!/usr/bin/php -f
<?php
/**
 * System.spoje.net - Odeslání Upomínek
 *
 * @author     Vítězslav Dvořák <info@vitexsofware.cz>
 * @copyright  (G) 2018 Vitex Software
 */
define('EASE_APPNAME', 'ClientsNotifier');
define('MODULES', './AbraFlexi/Reminder/Notifier');

require_once '../vendor/autoload.php';
$shared = new Ease\Shared();
if (file_exists('../client.json')) {
    $shared->loadConfig('../client.json', true);
}
if (file_exists('../reminder.json')) {
    $shared->loadConfig('../reminder.json', true);
}
$localer = new \Ease\Locale('cs_CZ', '../i18n', 'abraflexi-reminder');

$reminder = new \AbraFlexi\Reminder\Upominac();
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
                    isset(current($debts)['firma@showAs']) ? current($debts)['firma@showAs'] : current($debts)['firma'] ), 'debug');
    $reminder->customer->adresar->loadFromAbraFlexi($firma);
    $reminder->processNotifyModules(0, $debts, constant('MODULES') . '/ByEmail.php');
}