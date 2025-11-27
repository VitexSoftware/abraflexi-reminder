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

$skipping = $reminder->getClientsToSkip($allClients, $reminder);
$clientsToSkip = $skipping['clientsToSkip'];
$report = $skipping['report'];

$prepared = $reminder->prepareDebts($allDebts, $allClients, $clientsToSkip, $reminder);
$allDebtsByClient = $prepared['allDebtsByClient'];
$total = $prepared['total'];
$report = array_merge($report, $prepared['report']);

$report = $reminder->processDebts($allDebtsByClient, $allClients, $clientsToSkip, $reminder, $report);

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
