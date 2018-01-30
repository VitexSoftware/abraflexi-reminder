#!/usr/bin/php -f
<?php
/**
 * System.spoje.net - Odeslání Upomínek
 *
 * @author     Vítězslav Dvořák <info@vitexsofware.cz>
 * @copyright  (G) 2017 Vitex Software
 */
define('EASE_APPNAME', 'Reminder');

require_once '../vendor/autoload.php';
$shared = new Ease\Shared();
$shared->loadConfig('../client.json');
$shared->loadConfig('../reminder.json');

$reminder = new \FlexiPeeHP\Bricks\Upominac();
$reminder->logBanner();

$labelsRequied = ['UPOMINKA1', 'UPOMINKA2', 'UPOMINKA3', 'NEPLATIC', 'NEUPOMINKOVAT'];
$labeler       = new \FlexiPeeHP\Stitek();
foreach ($labelsRequied as $labelRequied) {
    if (!$labeler->recordExists(['kod' => $labelRequied])) {
        $labeler->insertToFlexiBee([
            "kod" => $labelRequied,
            "nazev" => $labelRequied,
            "vsbAdr" => true
        ]);
        $labeler->addStatusMessage(sprintf(_('Requied label %s create'),
                $labelRequied),
            ($labeler->lastResponseCode == 201) ? 'success' : 'error' );
    }
}

$reminder->processAllDebts();

