#!/usr/bin/php -f
<?php

/**
 * System.spoje.net - Odeslání Upomínek
 *
 * @author     Vítězslav Dvořák <info@vitexsofware.cz>
 * @copyright  (G) 2017-2021 Vitex Software
 */
define('EASE_APPNAME', 'Debts');
define('MODULES', './n');
if (!defined('EASE_LOGGER')) {
    define('EASE_LOGGER', 'syslog|console|mail');
}
require_once '../vendor/autoload.php';
$shared = new Ease\Shared();
try {
    if (file_exists('../.env')) {
        $shared->loadConfig('../.env', true);
    }


    $labelsRequied = ['UPOMINKA1', 'UPOMINKA2', 'UPOMINKA3', 'NEPLATIC', 'NEUPOMINKOVAT'];
    $labeler = new \AbraFlexi\Stitek();
    foreach ($labelsRequied as $labelRequied) {
        if (!$labeler->recordExists(['kod' => $labelRequied])) {
            $labeler->insertToAbraFlexi([
                "kod" => $labelRequied,
                "nazev" => $labelRequied,
                "vsbAdr" => true
            ]);
            $labeler->addStatusMessage(sprintf(_('Requied label %s create'),
                            $labelRequied),
                    ($labeler->lastResponseCode == 201) ? 'success' : 'error' );
        }
    }
} catch (Exception $exc) {
    echo $exc->getMessage() . "\n";
    echo $exc->getTraceAsString();
}


