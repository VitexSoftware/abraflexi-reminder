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

namespace AbraFlexi\Reminder;

use AbraFlexi\Bricks\Customer;

/**
 * @author vitex
 *
 * @no-named-arguments
 */
interface notifier
{
    /**
     * Compile Reminder message with its contents.
     *
     * @param int $score Weeks after due date
     */
    public function compile(int $score, Customer $customer, array $clientDebts): bool;
}
