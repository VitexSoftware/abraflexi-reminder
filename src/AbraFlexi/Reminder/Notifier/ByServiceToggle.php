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

namespace AbraFlexi\Reminder\Notifier;

use AbraFlexi\Bricks\Customer;
use AbraFlexi\Stitek;
use Ease\Sand;

/**
 * Bridges the reminder pipeline to service disconnection.
 *
 * When a customer reaches reminder level 3 (already reminded twice, still
 * overdue) the configured disconnect label (default: ODPOJENO) is added to
 * their address-book record.  isp-tools monitors this label and blocks the
 * customer's internet service.
 *
 * Activation is opt-in: set SERVICE_TOGGLE_ENABLED=true in the environment.
 *
 * Env vars:
 *   SERVICE_TOGGLE_ENABLED    – "true" to activate (default: false / inactive)
 *   SERVICE_DISCONNECT_LABEL  – label to set on disconnect (default: ODPOJENO)
 *
 * @no-named-arguments
 */
class ByServiceToggle extends Sand implements \AbraFlexi\Reminder\notifier
{
    public array $result = [];

    public function __construct(\AbraFlexi\Reminder\Upominac $reminder, int $score, array $debts)
    {
        $this->setObjectName();
        $this->compile($score, $reminder->customer, $debts);
    }

    public function compile(int $score, Customer $customer, array $clientDebts): bool
    {
        if (!\Ease\Shared::cfg('SERVICE_TOGGLE_ENABLED', false)) {
            $this->result = ['action' => 'disabled'];

            return true;
        }

        $disconnectLabel = (string) \Ease\Shared::cfg('SERVICE_DISCONNECT_LABEL', 'ODPOJENO');
        $adresar = $customer->getAdresar();
        $customerId = $adresar->getMyKey();

        if (empty($customerId)) {
            $this->result = ['action' => 'skipped', 'reason' => 'no customer id'];

            return false;
        }

        if ($score < 3) {
            $this->result = ['action' => 'none', 'score' => $score];

            return true;
        }

        $raw = $adresar->getColumnsFromAbraFlexi(['stitky'], ['id' => $customerId]);
        $labels = Stitek::listToArray((string) ($raw[0]['stitky'] ?? ''));

        if (\array_key_exists($disconnectLabel, $labels)) {
            $this->result = ['action' => 'already_disconnected', 'label' => $disconnectLabel];

            return true;
        }

        $labels[$disconnectLabel] = $disconnectLabel;
        $response = $adresar->insertToAbraFlexi(['id' => $customerId, 'stitky' => $labels]);
        $success = !empty($response)
            && isset($response['success'])
            && ($response['success'] === 'true' || $response['success'] === true);

        $this->result = ['action' => 'disconnect', 'label' => $disconnectLabel, 'success' => $success];

        if ($success) {
            $this->addStatusMessage(
                sprintf('Customer %s disconnected via label %s', $customerId, $disconnectLabel),
                'success',
            );
        } else {
            $this->addStatusMessage(
                sprintf('Failed to set %s on customer %s', $disconnectLabel, $customerId),
                'error',
            );
        }

        return $success;
    }
}
