#!/usr/bin/php -f
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

use AbraFlexi\Functions;
use Ease\Shared;

/**
 * This file is part of the AbraFlexi Reminder package.
 *
 * https://github.com/VitexSoftware/abraflexi-reminder
 *
 * (c) Vítězslav Dvořák <http://vitexsoftware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
\define('EASE_APPNAME', 'AbraFlexi Inventarizace');

require_once '../vendor/autoload.php';
Shared::init(['ABRAFLEXI_URL', 'ABRAFLEXI_LOGIN', 'ABRAFLEXI_PASSWORD', 'ABRAFLEXI_COMPANY'], $argv[1] ?? '../.env');
$localer = new \Ease\Locale(Shared::cfg('LANG', 'cs_CZ'), '../i18n', 'abraflexi-reminder');
$reminder = new \AbraFlexi\Reminder\Upominac();

if (Shared::cfg('APP_DEBUG') === 'True') {
    $reminder->logBanner(Shared::cfg('OVERDUE_PATIENCE', 0));
}

$allDebts = $reminder->getAllDebts();
$allClients = $reminder->getCustomerList(['limit' => 0]);
$clientsToNotify = [];

foreach ($allDebts as $kod => $debtData) {
    if (\AbraFlexi\FakturaVydana::overdueDays($debtData['datSplat']) < Shared::cfg('OVERDUE_PATIENCE', 0)) {
        continue; // Patience
    }

    if (strstr($debtData['stitky'], Shared::cfg('NO_REMIND_LABEL', 'NEUPOMINAT'))) {
        $reminder->addStatusMessage(sprintf(_('I skip the %s for %s because of the set label on the document'), $debtData['kod'], $debtData['firma']->showAs), 'info');

        continue;
    }

    $firma = Functions::uncode((string) $debtData['firma']);

    if (\strlen($firma) && (\array_key_exists($firma, $allClients) === false)) {
        $reminder->addStatusMessage(sprintf(_('Unknown customer %s'), $firma), 'warning');

        continue;
    }

    if (\strlen($firma) && \array_key_exists(\Ease\Shared::cfg('NO_REMIND_LABEL', 'NEUPOMINAT'), $allClients[$firma]['stitky'])) {
        $reminder->addStatusMessage(sprintf(_('I skip the %s because of the set label for the Company'), $debtData['firma']->showAs));

        continue;
    }

    $clientsToNotify[$firma][$kod] = $debtData;
}

$counter = 0;

foreach ($clientsToNotify as $firma => $debts) {
    if ($firma) {
        if (empty(trim(Functions::uncode((string) $firma)))) {
            $reminder->addStatusMessage(sprintf(_('Invoices %s without Company assigned'), implode(',', array_keys($debts))), 'error');
        } else {
            $reminder->customer->adresar->dataReset();
            $reminder->customer->adresar->loadFromAbraFlexi(Functions::code((string) $firma));
            $reminder->addStatusMessage(sprintf(
                _('(%d / %d) %s '),
                $counter++,
                \count($clientsToNotify),
                isset(current($debts)['firma']->showAs) ? current($debts)['firma']->showAs : current($debts)['firma'],
            ), 'debug');
            $reminder->processNotifyModules(0, $debts);
        }
    } else {
        $reminder->addStatusMessage('unnamed company'.json_encode(array_keys($debts)));
    }
}
