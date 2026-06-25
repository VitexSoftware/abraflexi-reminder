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

use AbraFlexi\Reminder\Upominac;

/**
 * Integration tests for the invoice reminder lifecycle against a live AbraFlexi instance.
 *
 * Requires ABRAFLEXI_URL/LOGIN/PASSWORD/COMPANY in the project .env pointing to the
 * test company (flexibee-dev.spoje.net, spoje_net_s_r_o_).
 *
 * Tests use customer TEST-CI-KLIENT created/cleaned by IntegrationTestCase.
 * MUTE=true is set by IntegrationTestCase so no real emails are sent.
 *
 * @no-named-arguments
 */
class InvoiceLifecycleTest extends IntegrationTestCase
{
    private static Upominac $upominac;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::$upominac = new Upominac();
    }

    protected function setUp(): void
    {
        self::cleanupAllInvoices();
        self::setCustomerLabels([]);
    }

    /**
     * Customer with no overdue invoices → score 0, no reminder triggered.
     */
    public function testNoInvoices_ScoreIsZero(): void
    {
        $score = self::$upominac->getCustomerScore(self::$customerId);
        $this->assertSame(0, $score, 'Customer with no invoices should have score 0');
    }

    /**
     * Invoice overdue 1 day, fresh customer (no labels) → score 1.
     */
    public function testFreshCustomer_OneDayOverdue_ScoreOne(): void
    {
        self::createOverdueInvoice(daysOverdue: 1);
        $score = self::$upominac->getCustomerScore(self::$customerId);
        $this->assertSame(1, $score, 'Invoice 1 day overdue without any UPOMINKA labels → score 1');
    }

    /**
     * Invoice overdue 15 days, no labels → still score 1 (must escalate through level 1 first).
     */
    public function testFreshCustomer_FifteenDaysOverdue_ScoreOneNotThree(): void
    {
        self::createOverdueInvoice(daysOverdue: 15);
        $score = self::$upominac->getCustomerScore(self::$customerId);
        $this->assertSame(1, $score, 'Without UPOMINKA1 label, score cannot jump to 3');
    }

    /**
     * Invoice overdue 10 days, customer already has UPOMINKA1 → score 2.
     */
    public function testWithUpominka1_TenDaysOverdue_ScoreTwo(): void
    {
        self::createOverdueInvoice(daysOverdue: 10);
        self::setCustomerLabels(['UPOMINKA1']);
        $score = self::$upominac->getCustomerScore(self::$customerId);
        $this->assertSame(2, $score, 'UPOMINKA1 set + 10 days overdue → score 2');
    }

    /**
     * Invoice overdue 15 days, customer has UPOMINKA1 but no UPOMINKA2 → score 2, not 3.
     */
    public function testWithUpominka1Only_FifteenDaysOverdue_ScoreTwo(): void
    {
        self::createOverdueInvoice(daysOverdue: 15);
        self::setCustomerLabels(['UPOMINKA1']);
        $score = self::$upominac->getCustomerScore(self::$customerId);
        $this->assertSame(2, $score, 'Without UPOMINKA2 label, score cannot reach 3');
    }

    /**
     * Invoice overdue 15 days, customer has both UPOMINKA1 and UPOMINKA2 → score 3.
     */
    public function testWithUpominka1And2_FifteenDaysOverdue_ScoreThree(): void
    {
        self::createOverdueInvoice(daysOverdue: 15);
        self::setCustomerLabels(['UPOMINKA1', 'UPOMINKA2']);
        $score = self::$upominac->getCustomerScore(self::$customerId);
        $this->assertSame(3, $score, 'UPOMINKA1+2 set + 15 days overdue → score 3');
    }

    /**
     * Customer with UPOMINKA1+2+3 and overdue invoice → score stays at 3 (already reminded).
     */
    public function testAllLabelsSet_ScoreThree(): void
    {
        self::createOverdueInvoice(daysOverdue: 15);
        self::setCustomerLabels(['UPOMINKA1', 'UPOMINKA2', 'UPOMINKA3']);
        $score = self::$upominac->getCustomerScore(self::$customerId);
        $this->assertSame(3, $score);
    }

    /**
     * All debts paid → enableCustomer() removes reminder labels, score drops to 0.
     */
    public function testPaidOff_LabelsCleared(): void
    {
        self::setCustomerLabels(['UPOMINKA1', 'UPOMINKA2']);
        self::$upominac->enableCustomer('UPOMINKA1,UPOMINKA2', self::$customerId);

        $labels = self::getCustomerLabels();
        $this->assertArrayNotHasKey('UPOMINKA1', $labels);
        $this->assertArrayNotHasKey('UPOMINKA2', $labels);
    }

    /**
     * Customer with NEUPOMINAT label → score calculated but reminder suppressed in processUserDebts.
     */
    public function testNeupominatLabel_ScoreStillCalculated(): void
    {
        self::createOverdueInvoice(daysOverdue: 5);
        self::setCustomerLabels(['NEUPOMINAT']);
        $score = self::$upominac->getCustomerScore(self::$customerId);
        $this->assertSame(1, $score, 'Score is calculated independently of NEUPOMINAT label');
    }

    /**
     * Cleanup all invoices created by individual test methods.
     * Called in setUp() to ensure a clean slate between tests.
     */
    private static function cleanupAllInvoices(): void
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
}
