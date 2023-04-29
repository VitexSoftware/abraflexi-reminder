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

\Ease\Shared::init(['ABRAFLEXI_URL', 'ABRAFLEXI_LOGIN', 'ABRAFLEXI_PASSWORD', 'ABRAFLEXI_COMPANY'], isset($argv[1]) ? $argv[1] : '../.env');

$localer = new \Ease\Locale('cs_CZ', '../i18n', 'abraflexi-reminder');

$reminder = new \AbraFlexi\Reminder\Upominac();
if (\Ease\Functions::cfg('APP_DEBUG') == 'True') {
    $reminder->logBanner(\Ease\Shared::appName());
}
$allDebts = $reminder->getAllDebts();

$clientsToNotify = [];
foreach ($allDebts as $kod => $debtData) {
    $clientsToNotify[$debtData['firma']][$kod] = $debtData;
}


$counter = 0;
foreach ($clientsToNotify as $firma => $debts) {
    $reminder->addStatusMessage(sprintf(_('(%d / %d) %s '), $counter++,
                    count($clientsToNotify),
                    isset(current($debts)['firma']->showAs) ? current($debts)['firma']->showAs : current($debts)['firma'] ), 'debug');
    $reminder->customer->adresar->loadFromAbraFlexi($firma);
    $reminder->processNotifyModules(0, $debts, constant('MODULES') . '/ByEmail.php');
}
