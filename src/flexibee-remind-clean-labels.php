#!/usr/bin/php -f

<?php
/**
 * FlexiBee reminder - Clear Reminder Labels
 *
 * @author     Vítězslav Dvořák <info@vitexsofware.cz>
 * @copyright  (G) 2017-2020 Vitex Software
 */
use FlexiPeeHP\Reminder\Upominac;

define('EASE_APPNAME', 'Clean Remind Labels');



require_once '../vendor/autoload.php';
$shared = new Ease\Shared();
$shared->loadConfig('../client.json', true);
$shared->loadConfig('../reminder.json', true);
$localer = new \Ease\Locale('cs_CZ', '../i18n', 'flexibee-reminder');

$reminder = new Upominac();
$reminder->logBanner(constant('EASE_APPNAME'));


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
        $reminder->customer->adresar->setMyKey(\FlexiPeeHP\FlexiBeeRO::code($clientCode));
        $reminder->customer->adresar->setDataValue('stitky', implode(',', $clientInfo['stitky']));
        $reminder->customer->adresar->unsetLabel($labelsRequiedRaw);
        $reminder->addStatusMessage(++$pos . '/' . count($labeledClients) . $clientCode . ' ' . _('Labels Cleanup'), ($reminder->customer->adresar->lastResponseCode == 201) ? 'success' : 'warning' );
    }
}
