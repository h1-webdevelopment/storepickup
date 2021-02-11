<?php
/**
 * Store Pickup
 *
 * NOTICE OF LICENSE
 *
 * You are not authorized to modify, copy or redistribute this file.
 * Permissions are reserved by FMM Modules.
 *
 *  @author    FMM Modules
 *  @copyright 2020 FMM Modules All right reserved
 *  @license   FMM Modules
 */

class AdminPickupSettingsController extends ModuleAdminController
{
    public function init()
    {
        parent::init();
        Tools::redirectAdmin($this->module->getPickupLink());
    }
}
