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

$sql[] = 'CREATE TABLE IF NOT EXISTS ' . _DB_PREFIX_ . 'storepickup_address (
    `id_store`                  INT(11) UNSIGNED NOT NULL,
    `id_address`                INT(11) UNSIGNED NOT NULL,
    PRIMARY KEY                 (`id_store`, `id_address`)
    ) ENGINE=InnoDB             CHARSET=utf8';

$sql[] = 'CREATE TABLE IF NOT EXISTS ' . _DB_PREFIX_ . 'storepickup_cart (
    `id_store`                  INT(11) UNSIGNED NOT NULL,
    `id_cart`                   INT(11) UNSIGNED NOT NULL,
    `id_order`                  INT(11) UNSIGNED NOT NULL DEFAULT 0,
    `id_carrier`                INT(11) UNSIGNED NOT NULL DEFAULT 0,
    `store_alert`               TINYINT(2) NOT NULL DEFAULT 0,
    `customer_alert`            TINYINT(2) NOT NULL DEFAULT 0,
    `pickup_date`               DATETIME DEFAULT NULL,
    PRIMARY KEY                 (`id_cart`)
    ) ENGINE=InnoDB             CHARSET=utf8';

foreach ($sql as $query) {
    if (Db::getInstance()->execute($query) == false) {
        return false;
    }
}
