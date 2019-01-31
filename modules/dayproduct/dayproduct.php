<?php

require_once _PS_ROOT_DIR_.'/classes/SpecificPrice.php';
require_once __DIR__ . '/classes/DayProductHelperForm.php';

class DayProduct extends Module
{
    private $idSpecificPrice = NULL;
    private $errors;
    public  $preFill = array();

    public function __construct()
    {
        $this->name = 'dayproduct';
        $this->version = '1.0';
        $this->author = 'Juliya Sakharova';
        $this->displayName = 'Day product';
        $this->description = 'This module provides the functionality of adding a product of the day in your service 
                              via admin-panel where you can specify the product special price and the offer expiration date.';
        $this->bootstrap = true;
        parent::__construct();
    }

    public function install()
    {
        if (!parent::install()) {
            return false;
        }
    }

    public function getContent()
    {
        $confirmationMassage = $this->handleConfigurationForm();
        if (!empty($this->assignConfiguration())) {
            $assignConfiguration = $this->assignConfiguration();
            $this->preFill = array(
                'product'        => $assignConfiguration['id_product'],
                'reduction'      => $assignConfiguration['reduction'],
                'reduction_type' => $assignConfiguration['reduction_type'],
                'from'           => $assignConfiguration['from'],
                'to'             => $assignConfiguration['to'],
                'description'    => Configuration::get('DESCRIPTION'));
        }
        return  $confirmationMassage . $this->renderForm();

    }

    /**
     * Renders template via HelperForm Class
     *
     * @return string
     */
    private function renderForm()
    {
        $formHelper = new DayProductHelperForm($this);

        return $formHelper->getForm();
    }

    public function assignConfiguration()
    {
        $isSpecificPrice = Configuration::get('ID_SPECIFIC_PRICE');
        if(!empty($this->getSpecificPriceById($isSpecificPrice)))
        {
            $currentSpecificPrice = $this->getSpecificPriceById($isSpecificPrice);
            $this->preFill = array(
                'product'        => $currentSpecificPrice[0]['id_product'],
                'reduction'      => $currentSpecificPrice[0]['reduction_type'] == 'percentage' ? $currentSpecificPrice[0]['reduction'] * 100 : $currentSpecificPrice[0]['reduction'],
                'reduction_type' => $currentSpecificPrice[0]['reduction_type'],
                'from'           => $currentSpecificPrice[0]['from'],
                'to'             => $currentSpecificPrice[0]['to'],
                'description'    => Configuration::get('DESCRIPTION'));
        }
    }

    /**
     * Handles form submit
     */
    public function handleConfigurationForm()
    {
        if (Tools::isSubmit('submit'.$this->name))
        {
            $formValues=[
                'id_product'=> Tools::getValue('product'),
                'from' => Tools::getValue('from'),
                'to' => Tools::getValue('to'),
                'reduction' => Tools::getValue('reduction'),
                'reduction_type' => Tools::getValue('reduction_type')];

            $validationResult = $this->validateSpecificPrice($formValues);

            if (true === $validationResult) {
                $idCurrent = Configuration::get('ID_SPECIFIC_PRICE');
                if(!empty($this->getSpecificPriceById($idCurrent)))
                {
                    $this->deleteSpecificPriceById($idCurrent);
                }
                $this->saveSpecificPrice($formValues);
                $idSpecificPriceArray = SpecificPrice::getIdsByProductId($formValues['id_product']);
                $this->idSpecificPrice = $idSpecificPriceArray[0]['id_specific_price'];
                Configuration::updateValue('ID_SPECIFIC_PRICE', $this->idSpecificPrice);
                Configuration::updateValue('DESCRIPTION', Tools::getValue('description'));
                return $this->displayConfirmation('Settings updated :)');
            } else {
                return $this->displayError($this->errors);
            }
        }
    }

    public function saveSpecificPrice($formValues)
    {
        $specificPrice = new SpecificPrice;

        $specificPrice->id_product = (int)$formValues['id_product'];
        $specificPrice->id_product_attribute = 0;
        $specificPrice->id_shop = 0;
        $specificPrice->id_currency = 0;
        $specificPrice->id_country = 0;
        $specificPrice->id_group = 0;
        $specificPrice->id_customer = 0;
        $specificPrice->price = -1;
        $specificPrice->from_quantity = 1;
        $specificPrice->reduction = $formValues['reduction_type'] == 'percentage' ? $formValues['reduction'] / 100 : $formValues['reduction'];
        $specificPrice->reduction_tax = 1;
        $specificPrice->reduction_type = $formValues['reduction_type'];
        $specificPrice->from = $formValues['from'];
        $specificPrice->to = $formValues['to'];

        $specificPrice->add();

        $this->idSpecificPrice = SpecificPrice::getIdsByProductId($formValues['id_product']);

    }

    private function validateSpecificPrice(array $formValues)
    {
        if (empty($formValues['from']) || empty($formValues['to'])){
            $this->errors = 'Enter the start and the and date, please';
        } elseif ($formValues['reduction'] && !Validate::isReductionType($formValues['reduction_type'])) {
            $this->errors = 'Please select a discount type (amount or percentage).';
        }elseif (isset($formValues['reduction']) &&(!Validate::isPercentage($formValues['reduction'] || !(float)$formValues['reduction'])) || !Validate::isPrice($formValues['reduction'])) {
            $this->errors = 'Please enter the right discount amount';
        }elseif ((!isset($formValues['reduction'])) || (isset($formValues['reduction']) && !Validate::isPrice($formValues['reduction']))) {
            $this->errors = 'Invalid discount value';
        } elseif ($formValues['from'] && $formValues['to'] &&
            (!Validate::isDateFormat($formValues['from']) || !Validate::isDateFormat($formValues['to']))) {
            $this->errors = 'The from/to date is invalid.';
        } elseif ($formValues['from'] > $formValues['to']){
            $this->errors = 'Invalid date values';
        } elseif (!empty(SpecificPrice::getByProductId($formValues['id_product']))) {
            $this->errors = 'A specific price already exists for these product.';
        } else {
            return true;
        }
        return false;
    }

    public function getSelectedProductID()
    {
        $productName = Tools::getValue('product');
        $products = Product::getSimpleProducts($this->context->language->id );
        foreach ($products as $product){
            if ($product['name'] = $productName){
                return $product['id_product'];
            }
        }
    }

    public function getSpecificPriceById($id)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
			SELECT `id_product`, `reduction`, `reduction_type`,`from`,`to`
			FROM `' . _DB_PREFIX_ . 'specific_price`
			WHERE `id_specific_price` = ' . (int) $id);
    }
    public function deleteSpecificPriceById($id)
    {
        return Db::getInstance()->execute('
			DELETE FROM `' . _DB_PREFIX_ . 'specific_price`
			WHERE id_specific_price=' . (int) $id);
    }
}