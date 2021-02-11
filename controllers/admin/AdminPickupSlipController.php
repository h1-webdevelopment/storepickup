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

class AdminPickupSlipController extends AdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'delivery';

        $this->context = Context::getContext();

        parent::__construct();
    }

    public function renderForm()
    {
        $carriers = array(array('id_carrier' => 0, 'name' => $this->l('All Carrier')));
        $carriers = array_merge(
            $carriers,
            Carrier::getCarriers(
                $this->context->language->id,
                false,
                false,
                false,
                null,
                Carrier::ALL_CARRIERS
            )
        );

        $stores = array(array('id_store' => 0, 'name' => $this->l('All Store')));
        $stores = array_merge($stores, Pickup::getAllStores());

        $this->fields_form = array(
            'legend' => array(
                'title' => $this->l('Print PDF delivery slips'),
                'icon' => 'icon-print',
            ),
            'input' => array(
                array(
                    'type' => 'date',
                    'label' => $this->l('From'),
                    'name' => 'date_from',
                    'maxlength' => 10,
                    'required' => true,
                    'hint' => $this->l('Format: 2011-12-31 (inclusive).'),
                ),
                array(
                    'type' => 'date',
                    'label' => $this->l('To'),
                    'name' => 'date_to',
                    'maxlength' => 10,
                    'required' => true,
                    'hint' => $this->l('Format: 2012-12-31 (inclusive).'),
                ),
                array(
                    'type' => 'select',
                    'label' => $this->l('Store filter:'),
                    'name' => 'id_store',
                    'options' => array(
                        'query' => $stores,
                        'id' => 'id_store',
                        'name' => 'name',
                    ),
                ),
            ),
            'submit' => array(
                'title' => $this->l('Generate PDF file'),
                'icon' => 'process-icon-download-alt',
            ),
        );

        $this->fields_value = array(
            'date_from' => date('Y-m-d'),
            'date_to' => date('Y-m-d'),
            'id_store' => (int) Tools::getValue('id_store', 0),
        );
        return parent::renderForm();
    }

    public function postProcess()
    {
        if (Tools::isSubmit('submitAdd' . $this->table)) {
            if (!Validate::isDate(Tools::getValue('date_from'))) {
                $this->errors[] = $this->l('Invalid \'from\' date');
            }

            if (!Validate::isDate(Tools::getValue('date_to'))) {
                $this->errors[] = $this->l('Invalid \'to\' date');
            }

            if (!count($this->errors)) {
                $deliveryCollection = OrderPickupInvoice::getByDeliveryDateInterval(
                    Tools::getValue('date_from'),
                    Tools::getValue('date_to'),
                    (int) Tools::getValue('id_store')
                );

                if (count($deliveryCollection)) {
                    $AdminPickupSlip = $this->context->link->getAdminLink('AdminPickupSlip') . '&' . http_build_query(array(
                        'submitAction' => 'generateDeliverySlipsPDF',
                        'date_from' => urlencode(Tools::getValue('date_from')),
                        'date_to' => urlencode(Tools::getValue('date_to')),
                        'id_store' => (int) Tools::getValue('id_store', 0),
                    ));
                    Tools::redirectAdmin($AdminPickupSlip);
                } else {
                    $this->errors[] = $this->l('No delivery slip was found for this period.');
                }
            }
        } elseif ('generateDeliverySlipsPDF' == Tools::getIsset('submitAction')) {
            $pdfController = new AdminPcikupSlipsController();
            $pdfController->processGenerateDeliverySlipsPDF();
            exit();
        } elseif (Tools::isSubmit('vieworder')) {
            if (($id_order = Tools::getValue('id_order')) && Validate::isLoadedObject($order = new Order((int) $id_order))) {
                $tokenOrder = Tools::getAdminToken('AdminOrders' . (int) Tab::getIdFromClassName('AdminOrders') . (int) $this->context->employee->id);
                $orderLink = $this->context->link->getAdminLink('AdminOrders') . '&' . http_build_query(array(
                    'vieworder' => 1,
                    'id_order' => (int) $order->id,
                    'token' => $tokenOrder,
                ));
                Tools::redirectAdmin($orderLink);
            }
        } elseif (Tools::isSubmit('submitBulksendStoreAlertMailorder') || Tools::isSubmit('submitBulksendCustomerAlertMailorder')) {
            $orderBox = Tools::getValue('orderBox');
            if (isset($orderBox) && $orderBox) {
                $mailTemplate = (Tools::isSubmit('submitBulksendStoreAlertMailorder')) ? 'store_alert' : 'customer_alert';
                foreach ($orderBox as $id_order) {
                    if (Validate::isLoadedObject($order = new Order($id_order))) {
                        $file_attachement = array();
                        $orderInvoices = $order->getInvoicesCollection();
                        if (isset($orderInvoices) && $orderInvoices) {
                            $pdf = new PDF($orderInvoices, PDF::TEMPLATE_DELIVERY_SLIP, $this->context->smarty);
                            $file_attachement['mime'] = 'application/pdf';
                            $file_attachement['name'] = Configuration::get(
                                'PS_DELIVERY_PREFIX',
                                (int) $order->id_lang,
                                null,
                                $order->id_shop
                            ) . sprintf('%06d', $order->delivery_number) . '.pdf';
                            $file_attachement['content'] = $pdf->render(false);

                            // sending delivery slip to corresponding store
                            if (($id_store = Pickup::getIdStoreByOrder($order->id)) && Validate::isLoadedObject($store = new Store((int) $id_store, $order->id_lang))) {
                                $customer = new Customer($order->id_customer);
                                $email = $store->email;
                                $name = $store->name;
                                if (Tools::isSubmit('submitBulksendCustomerAlertMailorder')) {
                                    $email = $customer->email;
                                    $name = $customer->firstname . ' ' . $customer->lastname;
                                }

                                if (empty($email) || !Validate::isEmail($email)) {
                                    $this->errors[] = sprintf($this->l('"%s" has invalid email.'), $name);
                                } else {
                                    $storeData = Pickup::getStoreByOrder($order->id, $order->id_lang);
                                    $currency = new Currency($order->id_currency);
                                    $invoice = new Address((int) $order->id_address_invoice);
                                    $delivery = new Address((int) $order->id_address_delivery);

                                    $st_address = AddressFormat::generateAddress($delivery, array(), '<br />');
                                    $st_address = str_replace($store->name, '', $st_address);
                                    $st_address = str_replace($delivery->firstname . ' ' . $delivery->lastname, '', $st_address);
                                    $st_address = ltrim($st_address);
                                    $data = array(
                                        '{email}' => $customer->email,
                                        '{store_name}' => $store->name,
                                        '{lastname}' => $customer->lastname,
                                        '{firstname}' => $customer->firstname,
                                        '{delivery_block_html}' => $st_address,
                                        '{order_name}' => $order->getUniqReference(),
                                        '{payment}' => Tools::substr($order->payment, 0, 255),
                                        '{total_paid}' => Tools::displayPrice($order->total_paid, $currency, false),
                                        '{total_discounts}' => Tools::displayPrice($order->total_discounts, $currency, false),
                                        '{total_wrapping}' => Tools::displayPrice($order->total_wrapping, $currency, false),
                                        '{total_shipping}' => Tools::displayPrice($order->total_shipping, $currency, false),
                                        '{date}' => Tools::displayDate(date('Y-m-d H:i:s', strtotime($order->date_add)), null, 1),
                                        '{total_products}' => Tools::displayPrice(Product::getTaxCalculationMethod() == PS_TAX_EXC ? $order->total_products : $order->total_products_wt, $currency, false),
                                        '{total_tax_paid}' => Tools::displayPrice(($order->total_products_wt - $order->total_products) + ($order->total_shipping_tax_incl - $order->total_shipping_tax_excl), $currency, false),
                                        '{pickup_date}' => Tools::displayDate($storeData['pickup_date'], null, 1),
                                        '{invoice_block_html}' => $this->getFormatedAddress(
                                            $invoice,
                                            '<br />',
                                            array(
                                                'firstname' => '<span style="font-weight:bold;">%s</span>',
                                                'lastname' => '<span style="font-weight:bold;">%s</span>',
                                            )
                                        ),
                                    );
                                    if (Mail::Send(
                                        (int) $order->id_lang,
                                        $mailTemplate,
                                        $this->l('Pickup Slip'),
                                        $data,
                                        $email,
                                        $name,
                                        null,
                                        null,
                                        $file_attachement,
                                        null,
                                        _PS_MODULE_DIR_ . 'storepickup/mails/',
                                        false,
                                        (int) $order->id_shop
                                    )) {
                                        if (Pickup::updateStoreByCart($order->id_cart, array($mailTemplate => (int) true))) {
                                            $this->confirmations[] = $this->l('Email alert successfully sent to selected stores.');
                                        } else {
                                            $this->confirmations[] = $this->l('Pickup slip successfully sent to selected customers.');
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        } else {
            parent::postProcess();
        }
    }

    public function initContent()
    {
        $this->initTabModuleList();
        $this->initPageHeaderToolbar();
        $this->show_toolbar = false;
        $this->content .= $this->renderForm();
        $this->content .= $this->renderStoreOrders();
        $this->context->smarty->assign(array(
            'content' => $this->content,
            'url_post' => self::$currentIndex . '&token=' . $this->token,
            'show_page_header_toolbar' => $this->show_page_header_toolbar,
            'page_header_toolbar_title' => $this->page_header_toolbar_title,
            'page_header_toolbar_btn' => $this->page_header_toolbar_btn,
        ));
    }

    protected function renderStoreOrders()
    {
        $fields_list = array(
            'id_store' => array(
                'title' => $this->l('ID'),
                'type' => 'text',
                'search' => false,
                'orderby' => false,
            ),
            'store_name' => array(
                'title' => $this->l('Store'),
                'type' => 'text',
                'search' => false,
                'orderby' => false,
            ),
            'store_email' => array(
                'title' => $this->l('Store Email'),
                'type' => 'text',
                'search' => false,
                'orderby' => false,
            ),
            'pickup_date' => array(
                'title' => $this->l('Pickup Date'),
                'type' => 'datetime',
                'search' => false,
                'orderby' => false,
            ),
            'reference' => array(
                'title' => $this->l('Order Reference'),
                'type' => 'text',
                'search' => false,
                'orderby' => false,
            ),
            'osname' => array(
                'title' => $this->l('Order Status'),
                'type' => 'text',
                'color' => 'color',
                'search' => false,
                'orderby' => false,
            ),
            'customer' => array(
                'title' => $this->l('Customer'),
                'search' => false,
                'orderby' => false,
            ),
            'customer_alert' => array(
                'title' => $this->l('Customer Alert Status'),
                'align' => 'text-center',
                'callback' => 'getAlertStatus',
                'callback_obj' => $this,
                'search' => false,
                'orderby' => false,
            ),
            'store_alert' => array(
                'title' => $this->l('Store Alert Status'),
                'align' => 'text-center',
                'callback' => 'getAlertStatus',
                'callback_obj' => $this,
                'search' => false,
                'orderby' => false,
            ),
            'id_pdf' => array(
                'title' => $this->l('PDF'),
                'align' => 'text-center',
                'callback' => 'printPDFIcons',
                'callback_obj' => $this,
                'orderby' => false,
                'search' => false,
                'remove_onclick' => true,
            ),
            'id_carrier' => array(
                'title' => $this->l('Alert'),
                'align' => 'text-center',
                'callback' => 'sendAlert',
                'callback_obj' => $this,
                'search' => false,
                'orderby' => false,
            ),
        );

        $helper = new HelperList();
        $helper->shopLinkType = '';
        $helper->no_link = true;
        $helper->bulk_actions = true;
        $helper->simple_header = false;
        $helper->table = 'order';
        $helper->identifier = 'id_order';
        $helper->actions = array('view');
        $helper->title = $this->l('Store Orders');

        $listData = Pickup::getStoreOrders($this->context->language->id);
        // skip rows without delivey slip
        $allowBulkActions = false;
        if (isset($listData) && $listData) {
            foreach ($listData as $data) {
                if (Validate::isLoadedObject($order = new Order($data['id_order']))) {
                    if ($order->delivery_number) {
                        $allowBulkActions = true;
                    } else {
                        $helper->list_skip_actions['delete'] = array($order->id);
                    }
                }
            }
        }

        $helper->bulk_actions = false;
        if ($allowBulkActions) {
            $helper->bulk_actions = array(
                'sendStoreAlertMail' => array(
                    'text' => $this->l('Send Delivery Slip to Store'),
                    'icon' => 'icon-paper-plane',
                ),
                'sendCustomerAlertMail' => array(
                    'text' => $this->l('Send Pickup Slip to Customer'),
                    'icon' => 'icon-envelope',
                ),
            );
        }

        $helper->listTotal = count($listData);
        $helper->show_toolbar = true;
        $helper->token = $this->token;
        $helper->currentIndex = self::$currentIndex;

        $helper->_pagination = array(10, 20, 50, 100);
        $helper->_default_pagination = 10;
        /* Paginate the result */
        $page = ($page = Tools::getValue('submitFilter' . $helper->table)) ? $page : 1;
        $pagination = ($pagination = Tools::getValue($helper->table . '_pagination')) ? $pagination : 10;
        $listData = $this->paginateList($listData, $page, $pagination);
        return $helper->generateList($listData, $fields_list);
    }

    public function getAlertStatus($isAlert)
    {
        return '<img src="' . __PS_BASE_URI__ . 'modules/storepickup/views/img/email_alert_' . $isAlert . '.png">';
    }

    public function sendAlert($id, $tr)
    {
        $order = new Order($tr['id_order']);
        if (!Validate::isLoadedObject($order)) {
            return '';
        }

        $this->context->smarty->assign(array(
            'order' => $order,
            'id_store' => $tr['id_store'],
        ));
        return $this->context->smarty->fetch(_PS_MODULE_DIR_ . 'storepickup/views/templates/admin/slip-alert.tpl');
    }

    public function printPDFIcons($id_order, $tr)
    {
        static $valid_order_state = array();

        $order = new Order($id_order);
        if (!Validate::isLoadedObject($order)) {
            return '';
        }

        if (!isset($valid_order_state[$order->current_state])) {
            $valid_order_state[$order->current_state] = Validate::isLoadedObject($order->getCurrentOrderState());
        }

        if (!$valid_order_state[$order->current_state]) {
            return '';
        }

        $adminPdf = $this->context->link->getAdminLink('AdminPdf') . '&' . http_build_query(array(
            'submitAction' => 'generateDeliverySlipPDF',
            'id_order' => (int) $order->id,
        ));

        $this->context->smarty->assign(array(
            'order' => $order,
            'tr' => $tr,
            'adminPdf' => $adminPdf,
        ));

        return $this->context->smarty->fetch(_PS_MODULE_DIR_ . 'storepickup/views/templates/admin/print-pdf-icon.tpl');
    }
    /* helper list paginate */
    public function paginateList($content, $page = 1, $pagination = 10)
    {
        if (count($content) > $pagination) {
            $content = array_slice($content, $pagination * ($page - 1), $pagination);
        }
        return $content;
    }

    public function ajaxProcessSendStoreAlert()
    {
        $response = array(
            'hasError' => true,
            'msg' => $this->l('Email sending failed.'),
        );

        $id_order = (int) Tools::getValue('id_order');
        $id_store = (int) Tools::getValue('id_store');
        $to = Tools::getValue('to', 'store');
        if ($id_order && Validate::isLoadedObject($order = new Order($id_order))) {
            if ($id_store && Validate::isLoadedObject($store = new Store($id_store, $order->id_lang))) {
                $orderInvoices = $order->getInvoicesCollection();
                $mailTemplate = ('store' == $to) ? 'store_alert' : 'customer_alert';
                if (isset($orderInvoices) && $orderInvoices) {
                    $pdf = new PDF($orderInvoices, PDF::TEMPLATE_DELIVERY_SLIP, $this->context->smarty);
                    $file_attachement = array();
                    $file_attachement['mime'] = 'application/pdf';
                    $file_attachement['name'] = Configuration::get(
                        'PS_DELIVERY_PREFIX',
                        (int) $order->id_lang,
                        null,
                        $order->id_shop
                    ) . sprintf('%06d', $order->delivery_number) . '.pdf';
                    $file_attachement['content'] = $pdf->render(false);

                    // sending delivery slip to store
                    $customer = new Customer($order->id_customer);
                    $email = $store->email;
                    $name = $store->name;
                    if ('customer' == $to) {
                        $email = $customer->email;
                        $name = $customer->firstname . ' ' . $customer->lastname;
                    }

                    if (empty($email) || !Validate::isEmail($email)) {
                        $response['hasError'] = true;
                        $response['msg'] = sprintf($this->l('"%s" has invalid email.'), $name);
                    } else {
                        $storeData = Pickup::getStoreByOrder($order->id, $order->id_lang);
                        $currency = new Currency($order->id_currency);
                        $invoice = new Address((int) $order->id_address_invoice);
                        $delivery = new Address((int) $order->id_address_delivery);

                        $st_address = AddressFormat::generateAddress($delivery, array(), '<br />');
                        $st_address = str_replace($store->name, '', $st_address);
                        $st_address = str_replace($delivery->firstname . ' ' . $delivery->lastname, '', $st_address);
                        $st_address = ltrim($st_address);
                        $data = array(
                            '{email}' => $customer->email,
                            '{store_name}' => $store->name,
                            '{lastname}' => $customer->lastname,
                            '{firstname}' => $customer->firstname,
                            '{delivery_block_html}' => $st_address,
                            '{order_name}' => $order->getUniqReference(),
                            '{payment}' => Tools::substr($order->payment, 0, 255),
                            '{total_paid}' => Tools::displayPrice($order->total_paid, $currency, false),
                            '{total_discounts}' => Tools::displayPrice($order->total_discounts, $currency, false),
                            '{total_wrapping}' => Tools::displayPrice($order->total_wrapping, $currency, false),
                            '{total_shipping}' => Tools::displayPrice($order->total_shipping, $currency, false),
                            '{date}' => Tools::displayDate(date('Y-m-d H:i:s', strtotime($order->date_add)), null, 1),
                            '{total_products}' => Tools::displayPrice(Product::getTaxCalculationMethod() == PS_TAX_EXC ? $order->total_products : $order->total_products_wt, $currency, false),
                            '{total_tax_paid}' => Tools::displayPrice(($order->total_products_wt - $order->total_products) + ($order->total_shipping_tax_incl - $order->total_shipping_tax_excl), $currency, false),
                            '{pickup_date}' => Tools::displayDate($storeData['pickup_date'], null, 1),
                            '{invoice_block_html}' => $this->getFormatedAddress(
                                $invoice,
                                '<br />',
                                array(
                                    'firstname' => '<span style="font-weight:bold;">%s</span>',
                                    'lastname' => '<span style="font-weight:bold;">%s</span>',
                                )
                            ),
                        );

                        if (Mail::Send(
                            (int) $order->id_lang,
                            $mailTemplate,
                            $this->l('Delivery Slip'),
                            $data,
                            $email,
                            $name,
                            null,
                            null,
                            $file_attachement,
                            null,
                            _PS_MODULE_DIR_ . 'storepickup/mails/',
                            false,
                            (int) $order->id_shop
                        )) {
                            $response['hasError'] = false;
                            if (Pickup::updateStoreByCart($order->id_cart, array($mailTemplate => (int) true))) {
                                $response['msg'] = sprintf($this->l('Email alert successfully sent to stores: "%s".'), $name);
                            } else {
                                $response['msg'] = sprintf($this->l('Pickup slip successfully sent to customer "%s".'), $name);
                            }
                        }
                    }
                }
            }
        }
        die(json_encode($response));
    }

    /**
     * @param object Address $the_address that needs to be txt formated
     *
     * @return string the txt formated address block
     */
    protected function getFormatedAddress(Address $the_address, $line_sep, $fields_style = array())
    {
        return AddressFormat::generateAddress($the_address, array('avoid' => array()), $line_sep, ' ', $fields_style);
    }

    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);
        Media::addJsDef(array('storeSlipController' => $this->context->link->getAdminLink('AdminPickupSlip')));
        $this->addCss(_PS_MODULE_DIR_ . 'storepickup/views/css/pickup-slip.css');
        $this->addJs(_PS_MODULE_DIR_ . 'storepickup/views/js/pickup-slip.js');
    }
}
