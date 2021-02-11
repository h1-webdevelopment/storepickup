<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file.
 * You are not authorized to modify, copy or redistribute this file.
 * Permissions are reserved by FME Modules.
 *
 *  @author    FMM Modules
 *  @copyright FME Modules 2020
 *  @license   Single domain
 */

$sql = array();

$sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'storepickup_address`';

$sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'storepickup_cart`';

foreach ($sql as $query) {
    if (false === Db::getInstance()->execute($query)) {
        return false;
    }
}
