#!/usr/bin/php -f

<?php

/**
 * AbraFlexi reminder - Clear Reminder Labels
 *
 * @author     Vítězslav Dvořák <info@vitexsofware.cz>
 * @copyright  (G) 2017-2020 Vitex Software
 */

use Ease\Locale;
use Ease\Shared;
use AbraFlexi\RO;
use AbraFlexi\Reminder\Upominac;

define('EASE_APPNAME', 'Clean Remind Labels');



require_once '../vendor/autoload.php';
$shared = new Shared();
if (file_exists('../client.json')) {
    $shared->loadConfig('../client.json', true);
}
if (file_exists('../reminder.json')) {
    $shared->loadConfig('../reminder.json', true);
}
$localer = new Locale('cs_CZ', '../i18n', 'abraflexi-reminder');

$reminder = new Upominac();
$reminder->logBanner(constant('EASE_APPNAME'));


$labelsRequiedRaw = ['UPOMINKA1', 'UPOMINKA2', 'UPOMINKA3', 'NEPLATIC'];

foreach ($labelsRequiedRaw as $label) {
    $labelsRequied[] = "stitky='code:" . $label . "'";
}

$labeledClients = $reminder->getCustomerList([implode(' or ', $labelsRequied)]);
if (empty($labeledClients)) {
    $reminder->addStatusMessage(__('None to clear'));
} else {
    $pos = 0;
    foreach ($labeledClients as $clientCode => $clientInfo) {
        $reminder->customer->adresar->setMyKey(RO::code($clientCode));
        $reminder->customer->adresar->setDataValue('stitky', implode(',', $clientInfo['stitky']));
        $reminder->customer->adresar->unsetLabel($labelsRequiedRaw);
        $reminder->addStatusMessage(++$pos . '/' . count($labeledClients) . ' ' . $clientCode . ' ' . __('Labels Cleanup'), ($reminder->customer->adresar->lastResponseCode == 201) ? 'success' : 'warning' );
    }
}
