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

\define('EASE_APPNAME', 'ReminderInit');

require_once '../vendor/autoload.php';
\Ease\Shared::init(['ABRAFLEXI_URL', 'ABRAFLEXI_LOGIN', 'ABRAFLEXI_PASSWORD', 'ABRAFLEXI_COMPANY'], $argv[1] ?? '../.env');

try {
    $labelsRequied = ['UPOMINKA1' => _('First Remind was send'), 'UPOMINKA2' => _('Second Remind was send'), 'UPOMINKA3' => _('Third Remind was send'), 'NEPLATIC' => _('defaulter'), 'NEUPOMINAT' => _('Do not Remind'), 'DATA_BOX' => _('Send reminds to Czech Data Box')];
    $labeler = new \AbraFlexi\Stitek();

    if (\Ease\Shared::cfg('APP_DEBUG') === 'True') {
        $labeler->logBanner(\Ease\Shared::appName());
    }

    foreach ($labelsRequied as $labelRequied => $labelDescription) {
        if (!$labeler->recordExists(['kod' => $labelRequied])) {
            $labeler->insertToAbraFlexi([
                'kod' => $labelRequied,
                'nazev' => $labelDescription,
                'vsbAdr' => true,
            ]);
            $labeler->addStatusMessage(
                sprintf(
                    _('Requied label %s create'),
                    $labelRequied,
                ),
                ($labeler->lastResponseCode === 201) ? 'success' : 'error',
            );
        }
    }
} catch (Exception $exc) {
    echo $exc->getMessage()."\n";
    echo $exc->getTraceAsString();
}
