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

namespace Tests\AbraFlexi\Reminder;

use AbraFlexi\Adresar;
use AbraFlexi\FakturaVydana;
use AbraFlexi\Stitek;
use PHPUnit\Framework\TestCase;

/**
 * Abstract base for integration tests that require a live AbraFlexi connection.
 *
 * Creates a test customer and overdue invoices before the suite, cleans up after.
 *
 * @no-named-arguments
 */
abstract class IntegrationTestCase extends TestCase
{
    protected static string $customerCode = 'TEST-CI-KLIENT';
    protected static int $customerId = 0;
    protected static array $invoiceIds = [];
    protected static Adresar $adresar;
    protected static FakturaVydana $invoicer;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        putenv('MUTE=true');
        \Ease\Shared::cfg('MUTE', 'true');

        self::$adresar = new Adresar();
        self::$invoicer = new FakturaVydana();

        self::ensureCustomerExists();
    }

    public static function tearDownAfterClass(): void
    {
        self::cleanupInvoices();
        self::cleanupCustomerLabels();
        parent::tearDownAfterClass();
    }

    /**
     * Create (or find) the CI test customer.
     */
    protected static function ensureCustomerExists(): void
    {
        $existing = self::$adresar->getColumnsFromAbraFlexi(
            ['id', 'kod', 'stitky'],
            ['kod' => self::$customerCode, 'limit' => 1],
        );

        if (!empty($existing)) {
            self::$customerId = (int) $existing[0]['id'];

            return;
        }

        self::$adresar->dataReset();
        self::$adresar->insertToAbraFlexi([
            'kod' => self::$customerCode,
            'nazev' => 'CI Test Customer (auto-generated)',
            'email' => 'ci-test@example.invalid',
        ]);
        self::$customerId = (int) self::$adresar->getLastInsertedId();
    }

    /**
     * Create an overdue invoice for the test customer.
     *
     * @param int   $daysOverdue Days past the due date (positive = overdue)
     * @param float $amount
     */
    protected static function createOverdueInvoice(int $daysOverdue = 10, float $amount = 1000.0): int
    {
        $dueDate = (new \DateTime("-{$daysOverdue} days"))->format('Y-m-d');
        $issueDate = (new \DateTime("-{$daysOverdue} days -7 days"))->format('Y-m-d');

        self::$invoicer->dataReset();
        self::$invoicer->insertToAbraFlexi([
            'firma' => \AbraFlexi\Code::ensure(self::$customerCode),
            'datVyst' => $issueDate,
            'datSplat' => $dueDate,
            'bezPolozek' => true,
            'sumZklZakl' => $amount,
            'popis' => 'CI integration test invoice',
        ]);
        $id = (int) self::$invoicer->getLastInsertedId();

        if ($id > 0) {
            self::$invoiceIds[] = $id;
        }

        return $id;
    }

    /**
     * Set customer labels (replaces all existing labels).
     *
     * @param string[] $labels
     */
    protected static function setCustomerLabels(array $labels): void
    {
        self::$adresar->dataReset();

        if (empty($labels)) {
            self::$adresar->insertToAbraFlexi([
                'id' => self::$customerId,
                'stitky@removeAll' => 'true',
                'stitky' => [],
            ]);
        } else {
            self::$adresar->insertToAbraFlexi([
                'id' => self::$customerId,
                'stitky@removeAll' => 'true',
                'stitky' => $labels,
            ]);
        }
    }

    /**
     * Get current labels of the test customer from AbraFlexi.
     *
     * @return array<string, string>
     */
    protected static function getCustomerLabels(): array
    {
        $raw = self::$adresar->getColumnsFromAbraFlexi(
            ['stitky'],
            ['id' => self::$customerId, 'limit' => 1],
        );

        if (empty($raw)) {
            return [];
        }

        return Stitek::listToArray((string) ($raw[0]['stitky'] ?? ''));
    }

    /**
     * Delete all invoices created during the test suite.
     */
    private static function cleanupInvoices(): void
    {
        foreach (self::$invoiceIds as $id) {
            self::$invoicer->dataReset();
            self::$invoicer->setMyKey($id);

            try {
                self::$invoicer->deleteFromAbraFlexi();
            } catch (\Throwable) {
            }
        }

        self::$invoiceIds = [];
    }

    /**
     * Remove all reminder/disconnection labels from the test customer.
     */
    private static function cleanupCustomerLabels(): void
    {
        if (self::$customerId === 0) {
            return;
        }

        self::setCustomerLabels([]);
    }
}
