#!/usr/bin/php -f

<?php

/**
 * AbraFlexi reminder - Clear Reminder Labels
 *
 * @author     Vítězslav Dvořák <info@vitexsofware.cz>
 * @copyright  (G) 2017-2022 Vitex Software
 */
use Ease\Locale;
use Ease\Shared;
use AbraFlexi\RO;
use AbraFlexi\Reminder\Upominac;

define('EASE_APPNAME', 'Clean Remind Labels');

require_once '../vendor/autoload.php';
$shared = new Shared();
if (file_exists('../.env')) {
    $shared->loadConfig('../.env', true);
}
$localer = new Locale('cs_CZ', '../i18n', 'abraflexi-reminder');

$reminder = new Upominac();
if(\Ease\Functions::cfg('APP_DEBUG') == 'True'){
    $reminder->logBanner(\Ease\Shared::appName());
}

$labelsRequiedRaw = ['UPOMINKA1', 'UPOMINKA2', 'UPOMINKA3', 'NEPLATIC'];

foreach ($labelsRequiedRaw as $label) {
    $labelsRequied[] = "stitky='code:" . $label . "'";
}

$labeledClients = $reminder->getCustomerList([implode(' or ', $labelsRequied)]);
if (empty($labeledClients)) {
    $reminder->addStatusMessage(_('None to clear'));
} else {
    $pos = 0;
    foreach ($labeledClients as $clientCode => $clientInfo) {
        $reminder->customer->adresar->setMyKey(RO::code($clientCode));
        $reminder->customer->adresar->setDataValue('stitky', implode(',', $clientInfo['stitky']));
        $reminder->customer->adresar->unsetLabel($labelsRequiedRaw);
        $reminder->addStatusMessage(++$pos . '/' . count($labeledClients) . ' ' . $clientCode . ' ' . _('Labels Cleanup'), ($reminder->customer->adresar->lastResponseCode == 201) ? 'success' : 'warning' );
    }
}
