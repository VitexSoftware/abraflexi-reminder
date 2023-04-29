#!/usr/bin/php -f

<?php

/**
 * AbraFlexi reminder - Clear Reminder Labels
 *
 * @author     Vítězslav Dvořák <info@vitexsofware.cz>
 * @copyright  (G) 2017-2023 Vitex Software
 */
use Ease\Locale;
use Ease\Shared;
use AbraFlexi\RO;
use AbraFlexi\Reminder\Upominac;

define('EASE_APPNAME', 'Clean Remind Labels');
require_once '../vendor/autoload.php';
\Ease\Shared::init(['ABRAFLEXI_URL', 'ABRAFLEXI_LOGIN', 'ABRAFLEXI_PASSWORD', 'ABRAFLEXI_COMPANY'], isset($argv[1]) ? $argv[1] : '../.env');
$localer = new Locale('cs_CZ', '../i18n', 'abraflexi-reminder');
$reminder = new Upominac();
if (\Ease\Functions::cfg('APP_DEBUG') == 'True') {
    $reminder->logBanner(\Ease\Shared::appName());
}


$labelsRequiedRaw = ['UPOMINKA1', 'UPOMINKA2', 'UPOMINKA3', 'NEPLATIC'];
$labelsRequied = [];
foreach ($labelsRequiedRaw as $label) {
    $labelsRequied[] = "stitky='code:" . $label . "'";
}

$pos = 0;
foreach ($reminder->getCustomerList([implode(' or ', $labelsRequied), 'limit' => 0]) as $clientCode => $clientInfo) {
    $reminder->customer->adresar->setMyKey(RO::code($clientCode));
    $reminder->customer->adresar->setDataValue('stitky', implode(',', $clientInfo['stitky']));
    $reminder->customer->adresar->unsetLabel($labelsRequiedRaw);
    $reminder->addStatusMessage(++$pos . '/' . count($reminder->customer->adresar->lastResult['adresar']) . ' ' . $clientCode . ' ' . _('Labels Cleanup'), ($reminder->customer->adresar->lastResponseCode == 201) ? 'success' : 'warning' );
}
if (!$pos) {
    $reminder->addStatusMessage(_('None to clear'));
}
