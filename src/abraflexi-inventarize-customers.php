#!/usr/bin/php -f
<?php

/**
 * Inventarize
 *
 * @author     Vítězslav Dvořák <info@vitexsofware.cz>
 * @copyright  (G) 2018-2024 Vitex Software
 */

define('EASE_APPNAME', 'AbraFlexi Inventarizace');
require_once '../vendor/autoload.php';
\Ease\Shared::init(['ABRAFLEXI_URL', 'ABRAFLEXI_LOGIN', 'ABRAFLEXI_PASSWORD', 'ABRAFLEXI_COMPANY'], isset($argv[1]) ? $argv[1] : '../.env');
$localer = new \Ease\Locale('cs_CZ', '../i18n', 'abraflexi-reminder');
$reminder = new \AbraFlexi\Reminder\Upominac();
if (\Ease\Shared::cfg('APP_DEBUG') == 'True') {
    $reminder->logBanner();
}
$allDebts = $reminder->getAllDebts();
$allClients = $reminder->getCustomerList(['limit' => 0]);
$clientsToNotify = [];
foreach ($allDebts as $kod => $debtData) {
    if (\AbraFlexi\FakturaVydana::overdueDays($debtData['datSplat']) < \Ease\Shared::cfg('OVERDUE_PATIENCE', 0)) {
        continue; // Patience
    }

    if (strstr($debtData['stitky'], \Ease\Shared::cfg('NO_REMIND_LABEL', 'NEUPOMINAT'))) {
        $reminder->addStatusMessage(sprintf(_('I skip the %s for %s because of the set label on the document'), $debtData['kod'], $debtData['firma']->showAs), 'info');
        continue;
    }

    $firma = \AbraFlexi\RO::uncode(strval($debtData['firma']));
    if (strlen($firma) && array_key_exists(\Ease\Shared::cfg('NO_REMIND_LABEL', 'NEUPOMINAT'), $allClients[$firma]['stitky'])) {
        $reminder->addStatusMessage(sprintf(_('I skip the %s because of the set label for the Company'), $debtData['firma']->showAs));
        continue;
    }
    $clientsToNotify[$firma][$kod] = $debtData;
}


$counter = 0;
foreach ($clientsToNotify as $firma => $debts) {
    if (empty(trim(\AbraFlexi\RO::uncode($firma)))) {
        $reminder->addStatusMessage(sprintf(_('Invoices %s without Company assigned'), implode(',', array_keys($debts))), 'error');
    } else {
        $reminder->customer->adresar->dataReset();
        $reminder->customer->adresar->loadFromAbraFlexi(\AbraFlexi\RO::code($firma));
        $reminder->addStatusMessage(sprintf(
            _('(%d / %d) %s '),
            $counter++,
            count($clientsToNotify),
            isset(current($debts)['firma']->showAs) ? current($debts)['firma']->showAs : current($debts)['firma']
        ), 'debug');
        $reminder->processNotifyModules(0, $debts);
    }
}
