<?php

require_once _PS_ROOT_DIR_.'/classes/SpecificPrice.php';
require_once __DIR__ . '/classes/DayProductHelperForm.php';

class DayProduct extends Module
{
    private $idSpecificPrice = NULL;
    private $errors;

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
       // $this->widgetTemplatePath = 'module:dayproduct/views/templates/hook/dayProductWidget.tpl';
    }

    public function install()
    {
        if (!parent::install() ||
            !$this->registerHook('displayHome')) {
            return false;
        }
    }

    public function getContent()
    {
        $confirmationMassage = $this->handleConfigurationForm();
        $this->assignConfiguration();

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
        $specificPriceValues = $this->getSpecificPriceById($isSpecificPrice);
        var_dump($isSpecificPrice); die();
        if(!empty($specificPriceValues))
        {
         $this->context->smarty->assign('$x', $specificPriceValues);
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
                'id_product'=> $this->getSelectedProductID(Tools::getValue('product')),
                'from' => Tools::getValue('start_date'),
                'to' => Tools::getValue('end_date'),
                'reduction' => Tools::getValue('discount_value'),
                'reduction_type' => Tools::getValue('discount_type')];

            $validationResult = $this->validateSpecificPrice($formValues);

            if (true === $validationResult) {
                $this->saveSpecificPrice($formValues);
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

        $this->idSpecificPrice = $this->getSpecificPriceId($specificPrice->id_product);

    }

    private function validateSpecificPrice(array $formValues)
    {
        if (empty($formValues['from']) || empty($formValues['to'])){
            $this->errors = 'Enter the start and the and date, please';
        } elseif ($formValues['reduction'] && !Validate::isReductionType($formValues['reduction_type'])) {
            $this->errors = 'Please select a discount type (amount or percentage).';
        }elseif ($formValues['reduction'] &&(!Validate::isPercentage($formValues['reduction'] || !(float)$formValues['reduction']))) {
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
    public function getSpecificPriceId($id_product)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
			SELECT MAX(`id_specific_price`)
			FROM `' . _DB_PREFIX_ . 'specific_price`
			WHERE `id_product` = ' . (int) $id_product);
    }

    public function getSpecificPriceById($id)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
			SELECT `id_product`, `reduction`, `reduction_type`,`from`,`to`
			FROM `' . _DB_PREFIX_ . 'specific_price`
			WHERE `id_specific_price` = ' . (int) $id);
    }

 /**   public function renderWidget($hookName, array $configuration)
    {
        $this->smarty->assign($this->getWidgetVariables($hookName, $configuration));
        return $this->fetch($this->widgetTemplatePath);
    }
    public function getWidgetVariables($hookName, array $configuration)
    {

    }**/
}