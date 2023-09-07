<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPInterface.php to edit this template
 */

namespace AbraFlexi\Reminder;

/**
 *
 * @author vitex
 */
interface notifier
{
    /**
     * Compile Reminder message with its contents
     *
     * @param int                         $score        Weeks after due date
     * @param Customer $customer
     * @param array                       $clientDebts
     * 
     * @return boolean
     */
    public function compile($score, $customer, $clientDebts);
}