#!/usr/bin/php -f

<?php

/**
 * AbraFlexi reminder - Clear Reminder Labels.
 *
 * author     Vítězslav Dvořák <info@vitexsofware.cz>
 */

use AbraFlexi\Code;
use AbraFlexi\Reminder\Upominac;
use Ease\Locale;
use Ease\Shared;

\define('EASE_APPNAME', 'Clean Remind Labels');

require_once '../vendor/autoload.php';
$exitcode = 0;
$options = getopt('o::e::', ['output::environment::']);
$report = [];
Shared::init(
    ['ABRAFLEXI_URL', 'ABRAFLEXI_LOGIN', 'ABRAFLEXI_PASSWORD', 'ABRAFLEXI_COMPANY'],
    \array_key_exists('environment', $options) ? $options['environment'] : (\array_key_exists('e', $options) ? $options['e'] : '../.env'),
);
$destination = \array_key_exists('output', $options) ? $options['output'] : Shared::cfg('RESULT_FILE', 'php://stdout');

$localer = new Locale(Shared::cfg('LANG', 'cs_CZ'), '../i18n', 'abraflexi-reminder');
$reminder = new Upominac();

if (Shared::cfg('APP_DEBUG') === 'True') {
    $reminder->logBanner(Shared::appName().' v'.Shared::appVersion());
}

$labelsRequiedRaw = ['UPOMINKA1', 'UPOMINKA2', 'UPOMINKA3', 'NEPLATIC'];
$labelsRequied = [];

foreach ($labelsRequiedRaw as $label) {
    $labelsRequied[] = "stitky='code:".$label."'";
}

$pos = 0;

foreach ($reminder->getCustomerList([implode(' or ', $labelsRequied), 'limit' => 0]) as $clientCode => $clientInfo) {
    $reminder->customer->adresar->setMyKey(Code::ensure($clientCode));
    $reminder->customer->adresar->setDataValue('stitky', implode(',', $clientInfo['stitky']));

    // Check if the customer has no debts
    if (empty($reminder->customer->getCustomerDebts())) {
        $reminder->customer->adresar->unsetLabel($labelsRequiedRaw);
        $reminder->addStatusMessage(++$pos.'/'.\count($reminder->customer->adresar->lastResult['adresar']).' '.$clientCode.' '._('Labels Cleanup'), ($reminder->customer->adresar->lastResponseCode === 201) ? 'success' : 'warning');
        $report['removed'][$clientCode] = $labelsRequiedRaw;
    } else {
        $reminder->addStatusMessage($clientCode.' '._('Customer has debts, labels not removed'), 'info');
    }
}

if (!$pos) {
    $reminder->addStatusMessage(_('None to clear'));
}

$written = file_put_contents($destination, json_encode($report, Shared::cfg('DEBUG') ? \JSON_PRETTY_PRINT : 0));
$reminder->addStatusMessage(sprintf(_('Saving result to %s'), $destination), $written ? 'success' : 'error');

exit($exitcode);
