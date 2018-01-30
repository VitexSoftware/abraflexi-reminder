#!/usr/bin/php -f
<?php
/**
 * System.spoje.net - Odeslání Upomínek
 *
 * @author     Vítězslav Dvořák <info@vitexsofware.cz>
 * @copyright  (G) 2017 Vitex Software
 */
define('EASE_APPNAME', 'Debts');

require_once '../vendor/autoload.php';
$shared = new Ease\Shared();
$shared->loadConfig('../client.json');
$shared->loadConfig('../reminder.json');

$reminder = new \FlexiPeeHP\Bricks\Upominac();
$reminder->logBanner();


$allDebths = $reminder->getDebths();
$reminder->addStatusMessage(sprintf(_('%d clients to remind process'),
        count($allDebths)));
$counter   = 0;
$total = [];
foreach ($allDebths as $cid => $debts) {
    $counter++;
    $howmuchRaw = $howmuch = [];
    foreach ($debts as $debt) {
        $curcode = FlexiPeeHP\FlexiBeeRO::uncode($debt['mena']);
        if (!isset($howmuchRaw[$curcode])) { $howmuchRaw[$curcode] = 0;}
        $howmuchRaw[$curcode] += $debt['sumCelkem'];
        if(!isset($total[$curcode])) $total[$curcode] = 0;
        $total[$curcode] += $debt['sumCelkem'];
    }
    foreach ($howmuchRaw as $cur => $price) {$howmuch[] = $price.' '.$cur;}

    $reminder->customer->adresar->loadFromFlexiBee($cid);
    $reminder->addStatusMessage(sprintf('(%d / %d) %s  %s %s [ %s ]',
            $counter, count($allDebths), implode(',', $howmuch),
            $reminder->customer->adresar->getDataValue('kod'),
            $reminder->customer->adresar->getDataValue('nazev'),
            $reminder->customer->adresar->getDataValue('stitky')
        ), 'debug');
}
$reminder->addStatusMessage(json_encode($total));
