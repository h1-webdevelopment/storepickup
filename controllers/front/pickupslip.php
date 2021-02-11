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

class StorepickupPickupslipModuleFrontController extends ModuleFrontController
{
    protected $order = null;

    public function init()
    {
        parent::init();
        $this->context = Context::getContext();

        if (!($this->context->customer->isLogged())) {
            Tools::redirect($this->context->link->getPageLink('my-account'));
        }

        if (!Validate::isLoadedObject($this->order = new Order((int)Tools::getValue('id_order')))) {
            die(Tools::displayError('Order not found.'));
        } elseif ($this->order->id_customer != $this->context->customer->id) {
            Tools::redirect($this->context->link->getPageLink('history'));
        }

        if (($action = Tools::getValue('submitAction')) && 'generatePickupSlipPDF' === $action) {
            return call_user_func(array($this, 'process'.Tools::toCamelCase($action)));
        } else {
            die(Tools::displayError('You do not have permission to access this.'));
        }
    }

    public function processGeneratePickupSlipPDF()
    {
        $deliverySlipList = $this->order->getInvoicesCollection();
        if (!count($deliverySlipList)) {
            Tools::redirect($this->context->link->getPageLink('history'));
        } else {
            $pdf = new PDF($deliverySlipList, PDF::TEMPLATE_DELIVERY_SLIP, $this->context->smarty);
            $pdf->render();
        }
        die();
    }
}
