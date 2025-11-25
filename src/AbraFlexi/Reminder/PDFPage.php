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

/**
 * Description of PDFPage.
 *
 * @author vitex
 *
 * @no-named-arguments
 */
class PDFPage extends \Ease\WebPage
{
    public function getPdf(): string
    {
        $html2pdf = new \Spipu\Html2Pdf\Html2Pdf();
        file_put_contents('/var/tmp/upominka.html', $this->getRendered());
        $html2pdf->writeHTML($this->getRendered());

        return $html2pdf->output();
    }
}
