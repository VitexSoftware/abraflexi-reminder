#!/usr/bin/php -f
<?php

/**
 * AbraFlexi Reminder - Prepare labels
 *
 * @author     Vítězslav Dvořák <info@vitexsofware.cz>
 * @copyright  (G) 2017-2023 Vitex Software
 */

define('EASE_APPNAME', 'ReminderInit');
require_once '../vendor/autoload.php';
\Ease\Shared::init(['ABRAFLEXI_URL', 'ABRAFLEXI_LOGIN', 'ABRAFLEXI_PASSWORD', 'ABRAFLEXI_COMPANY'], isset($argv[1]) ? $argv[1] : '../.env');
try {
    $labelsRequied = ['UPOMINKA1', 'UPOMINKA2', 'UPOMINKA3', 'NEPLATIC', 'NEUPOMINKOVAT'];
    $labeler = new \AbraFlexi\Stitek();
    if (\Ease\Shared::cfg('APP_DEBUG') == 'True') {
        $labeler->logBanner(\Ease\Shared::appName());
    }
    foreach ($labelsRequied as $labelRequied) {
        if (!$labeler->recordExists(['kod' => $labelRequied])) {
            $labeler->insertToAbraFlexi([
                "kod" => $labelRequied,
                "nazev" => $labelRequied,
                "vsbAdr" => true
            ]);
            $labeler->addStatusMessage(
                sprintf(
                    _('Requied label %s create'),
                    $labelRequied
                ),
                ($labeler->lastResponseCode == 201) ? 'success' : 'error'
            );
        }
    }
} catch (Exception $exc) {
    echo $exc->getMessage() . "\n";
    echo $exc->getTraceAsString();
}
