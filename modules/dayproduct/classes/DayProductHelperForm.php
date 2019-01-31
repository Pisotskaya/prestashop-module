<?php

/**
 *
 */
class DayProductHelperForm extends HelperForm
{
    public function __construct(Module $module)
     {
         parent::__construct();

         // Get default language
         $defaultLang = (int)Configuration::get('PS_LANG_DEFAULT');

         $this->module = $module;
         $this->name_controller = $module->name;
         $this->token = Tools::getAdminTokenLite('AdminModules');
         $this->currentIndex = AdminController::$currentIndex.'&configure='.$module->name;

         // Language
         $this->default_form_language = $defaultLang;
         $this->allow_employee_form_lang = $defaultLang;

         // Title and toolbar
         $this->title = $module->displayName;
         $this->show_toolbar = true;        // false -> remove toolbar
         $this->toolbar_scroll = true;      // yes - > Toolbar is always visible on the top of the screen.
         $this->submit_action = 'submit'.$module->name;
         $this->toolbar_btn = [
             'save' => [
                 'desc' => $module->l('Save'),
                 'href' => AdminController::$currentIndex.'&configure='.$module->name.'&save'.$module->name.
                     '&token='.Tools::getAdminTokenLite('AdminModules'),
             ],
             'back' => [
                 'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
                 'desc' => $module->l('Back to list')
             ]
         ];

         // Load current value
         $this->fields_value = array(
             'product'        => empty($module->preFill['product']) ? '' : $module->preFill['product'],
             'from'           => empty($module->preFill['from']) ? '': $module->preFill['from'],
             'to'             => empty($module->preFill['to']) ? '' : $module->preFill['to'],
             'reduction_type' => empty($module->preFill['reduction_type']) ? '' : $module->preFill['reduction_type'],
             'reduction'      => empty($module->preFill['reduction']) ? '' : $module->preFill['reduction'],
             'description'    => empty($module->preFill['description']) ? '' : $module->preFill['description']
         );
     }

    /**
     * Returns form
     */
     public function getForm()
     {
         $formFields = $this->generateFormFields();

         return parent::generateForm($formFields);
     }

    /**
     * Generate form fields array
     */
    private function generateFormFields()
    {
        $formFields[0]['form'] = [
            'legend' => [
                'title' => $this->l('Settings'),
                'icon' => 'icon-cogs'
            ],
            'input' => [
                [
                    'type' => 'select',
                    'label' => $this->l('Product:'),
                    'name' => 'product',
                    'desc' => $this->l('Choose a product of the day'),
                    'options' => array(
                        'query' => $this->getProductsListOptions(),
                        'id' => 'id_option',
                        'name' => 'name'
                    ),
                    'autocomplete' => true,
                    'required' => true
                ],
                $this->getDatePicker('start date'),
                $this->getDatePicker('final date'),
                [
                    'type' => 'text',
                    'label' => $this->l('Discount value:'),
                    'name' => 'reduction',
                    'desc' => $this->l('Choose a discount value'),
                    'required' => true
                ],
                [
                    'type' => 'select',
                    'label' => $this->l('Discount type:'),
                    'name' => 'reduction_type',
                    'desc' => $this->l('Choose a type of discount'),
                    'options' => array(
                        'query'=>array(
                            array('value' =>'percentage', 'name'  => '%'),
                            array('value' => 'amount', 'name'  => '$')),
                        'id'=>'value',
                        'name'=>'name'),
                    'required' => true
                ],
                [
                    'type' => 'textarea',
                    'tinymce' => true,
                    'autoload_rte' => true,
                    'required' => true,
                    'value' =>'tutu',
                    'label' => $this->l('Description:'),
                    'name' => 'description',
                    'desc' => $this->l('Enter your promo-description')
                ]

            ],
            'submit' => [
                'title' => $this->l('Save'),
                'class' => 'btn btn-default pull-right'
            ]
        ];

        return $formFields;
    }

    /**
     *  Returns date picker form fields
     */
    private function getDatePicker($option)
    {
        switch ($option) {
            case 'start date':
                $labelValue = $this->l('From:');
                $nameValue = 'from';
                $descValue = $this->l('Select the start date for display product');
               break;
            case 'final date':
                $labelValue = $this->l('To:');
                $nameValue = 'to';
                $descValue = $this->l('Select the final date for display product');
                break;
        };
        $datePicker = [
            'type'  => 'date',
            'label' => $labelValue,
            'name'  => $nameValue,
            'desc'  => $descValue,
            'required' => true
        ];
         return $datePicker;
    }

    /**
     *  Returns options for products selector
     */
    private function getProductsListOptions()
    {
        $products = Product::getSimpleProducts($this->context->language->id );
        $productsListOptions=[];
        foreach ($products as $product) {
            $productsListOptions[] = array(
                'id_option' => $product['id_product'],
                'name' => $product['name']
            );
        }
        return $productsListOptions;

    }

}