<?php

declare(strict_types=1);

/**
 * This file is part of the AbraFlexi Reminder package
 *
 * https://github.com/VitexSoftware/abraflexi-reminder
 *
 * (c) Vítězslav Dvořák <http://vitexsoftware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use AbraFlexi\Reminder\Upominac;
use Ease\Locale;
use Ease\Shared;

\define('EASE_APPNAME', 'AbraFlexi reminder');

require_once '../vendor/autoload.php';

function getClientsToSkip(array $allClients, Upominac $reminder): array
{
    $clientsToSkip = [];

    foreach ($allClients as $clientCodeRaw => $clientInfo) {
        if (\array_key_exists(Shared::cfg('NO_REMIND_LABEL', 'NEUPOMINAT'), $clientInfo['stitky'])) {
            $clientsToSkip[$clientCodeRaw] = $clientInfo;
        }
    }

    if (!empty($clientsToSkip)) {
        $reminder->addStatusMessage(implode(', ', array_keys($clientsToSkip)).'  '.\count($clientsToSkip).' '._('clients will be skipped'), 'warning');
    }

    return ['clientsToSkip' => $clientsToSkip, 'report' => ['skippedClients' => array_keys($clientsToSkip)]];
}

function prepareDebts(array $allDebts, array &$allClients, array $clientsToSkip, Upominac $reminder): array
{
    $allDebtsByClient = [];
    $total = [];
    $report = [];

    foreach ($allDebts as $code => $debt) {
        if (strstr($debt['stitky'], Shared::cfg('NO_REMIND_LABEL', 'NEUPOMINAT'))) {
            $reminder->addStatusMessage(sprintf(_('I skip the %s because of the set label %s'), $code, Shared::cfg('NO_REMIND_LABEL', 'NEUPOMINAT')), 'info');
            $report['skippedDocuments'][] = $code;

            continue;
        }

        if (empty($debt['firma'])) {
            $clientCodeShort = '';
        } else {
            $clientCodeShort = AbraFlexi\Code::strip((string) $debt['firma']);
        }

        if (\array_key_exists((string) $debt['firma'], $clientsToSkip)) {
            continue;
        }

        $curcode = AbraFlexi\Code::strip((string) $debt['mena']);

        if ($curcode === 'CZK') {
            $amount = (float) $debt['zbyvaUhradit'];
        } else {
            $amount = (float) $debt['zbyvaUhraditMen'];
        }

        if (!isset($total[$curcode])) {
            $total[$curcode] = 0;
        }

        if (!\array_key_exists('totals', $allClients[$clientCodeShort])) {
            $allClients[$clientCodeShort]['totals'] = [];
        }

        if (!\array_key_exists($curcode, $allClients[$clientCodeShort]['totals'])) {
            $allClients[$clientCodeShort]['totals'][$curcode] = $amount;
        } else {
            $allClients[$clientCodeShort]['totals'][$curcode] += $amount;
        }

        $total[$curcode] += $amount;
        $allDebtsByClient[$clientCodeShort][$code] = $debt;
    }

    return [
        'allDebtsByClient' => $allDebtsByClient,
        'total' => $total,
        'report' => $report,
    ];
}

function processDebts(array $allDebtsByClient, array $allClients, array $clientsToSkip, Upominac $reminder, array $report): array
{
    foreach ($allDebtsByClient as $clientCode => $clientDebts) {
        $clientCodeShort = AbraFlexi\Code::strip((string) $clientCode);

        if (empty(trim($clientCodeShort))) {
            $reminder->addStatusMessage(sprintf(_('Invoices %s without Company assigned'), implode(',', array_keys($clientDebts))), 'error');
        } else {
            if (\array_key_exists($clientCode, $clientsToSkip)) {
                continue;
            }

            $clientData = $allClients[$clientCodeShort];

            if ($clientCode) {
                $reminder->addStatusMessage(
                    $clientCodeShort.' '.
                    $clientData['nazev'].
                    ' ['.implode(',', $clientData['stitky']).'] '.
                    Upominac::formatTotals($clientData['totals']),
                    'success',
                );
            } else {
                $reminder->addStatusMessage(_('Missing Client CODE'), 'warning');
            }

            $report['reminded'][$clientCodeShort] = $reminder->processUserDebts($clientData, $clientDebts);
        }
    }

    return $report;
}

/**
 * Get today's Statements list.
 */
$options = getopt('o::e::', ['output::environment::']);
Shared::init(
    ['ABRAFLEXI_URL', 'ABRAFLEXI_LOGIN', 'ABRAFLEXI_PASSWORD', 'ABRAFLEXI_COMPANY'],
    \array_key_exists('environment', $options) ? $options['environment'] : (\array_key_exists('e', $options) ? $options['e'] : '../.env'),
);
$destination = \array_key_exists('o', $options) ? $options['o'] : (\array_key_exists('output', $options) ? $options['output'] : Shared::cfg('RESULT_FILE', 'php://stdout'));

$localer = new Locale(Shared::cfg('LANG', 'cs_CZ'), '../i18n', 'abraflexi-reminder');
$reminder = new Upominac();

if (strtolower(Shared::cfg('APP_DEBUG', 'false')) === 'true') {
    $reminder->logBanner();
}

$allDebts = $reminder->getAllDebts(['limit' => 0, "datSplat gte '".\AbraFlexi\Date::timestampToFlexiDate(mktime(0, 0, 0, (int) date('m'), (int) date('d') - (int) Shared::cfg('SURRENDER_DAYS', 365), (int) date('Y')))."' "]);
$allClients = $reminder->getCustomerList(['limit' => 0]);
$allClients[''] = ['kod' => '', 'nazev' => '('._('Company not assigned').')', 'stitky' => [
    Shared::cfg('NO_REMIND_LABEL', 'NEUPOMINAT') => Shared::cfg('NO_REMIND_LABEL', 'NEUPOMINAT')]];

$skipping = getClientsToSkip($allClients, $reminder);
$clientsToSkip = $skipping['clientsToSkip'];
$report = $skipping['report'];

$prepared = prepareDebts($allDebts, $allClients, $clientsToSkip, $reminder);
$allDebtsByClient = $prepared['allDebtsByClient'];
$total = $prepared['total'];
$report = array_merge($report, $prepared['report']);

$report = processDebts($allDebtsByClient, $allClients, $clientsToSkip, $reminder, $report);

$reminder->addStatusMessage(Upominac::formatTotals($total), 'success');

$exitcode = $reminder->getExitCode();

// Add required schema fields
$report['exitcode'] = $exitcode;
$report['status'] = $exitcode === 0 ? 'success' : 'error';
$report['timestamp'] = date('c');
$report['message'] = $exitcode === 0 ? _('Remind process finished successfully') : _('Remind process finished with errors');

if (!isset($report['artifacts'])) {
    $report['artifacts'] = new stdClass();
}

if (!isset($report['metrics'])) {
    $report['metrics'] = new stdClass();
}

$written = file_put_contents($destination, json_encode($report, Shared::cfg('DEBUG') ? \JSON_PRETTY_PRINT | \JSON_UNESCAPED_UNICODE : 0));

if ($written === false) {
    $exitcode = 1;
    $reminder->addStatusMessage(sprintf(_('Error saving result to %s'), $destination), 'error');
} else {
    $reminder->addStatusMessage(sprintf(_('Saving result to %s'), $destination), 'success');
}

exit($exitcode);
