<?php

/**
 * AbraFlexi Reminder Mailer PDF Page
 *
 * @author     Vítězslav Dvořák <info@vitexsofware.cz>
 * @copyright  (G) 2017-2020 Vitex Software
 */

namespace AbraFlexi\Reminder;

/**
 * Description of PDFPage
 *
 * @author vitex
 */
class PDFPage extends \Ease\WebPage {
    public function getPdf() {
        $html2pdf = new Html2Pdf();
        file_put_contents('/var/tmp/upominka.html', $this->getRendered());
        $html2pdf->writeHTML($this->getRendered());
        return $html2pdf->output();
    }
}
