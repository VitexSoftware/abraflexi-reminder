<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace FlexiPeeHP\Reminder;

use Spipu\Html2Pdf\Html2Pdf;

/**
 * Description of PDFPage
 *
 * @author vitex
 */
class PDFPage extends \Ease\WebPage
{

    public function getPdf()
    {
        $html2pdf = new Html2Pdf();
        $html2pdf->writeHTML($this->getRendered());
        return $html2pdf->output();
    }
}
