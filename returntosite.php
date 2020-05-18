<?php
/**
* 2007-2020 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2020 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

class Returntosite extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'returntosite';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'MarcinL';
        $this->need_instance = 1;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('returntosite');
        $this->description = $this->l('Wyświetla popup gdy user chce opuścić stronę ');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
       
        Configuration::updateValue('EP_DESCRIPTION', '<p>przykładowa treść</p>', true);
        Configuration::updateValue('EP_COLOR', '#ff3f64');

        include(dirname(__FILE__).'/sql/install.php');

        return parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('backOfficeHeader') &&
            $this->registerHook('displayFooterBefore');
            //$this->registerHook('displayLeftColumn');
    }

    public function uninstall()
    {
        Configuration::deleteByName('EP_DESCRIPTION');
        Configuration::deleteByName('EP_COLOR');


        include(dirname(__FILE__).'/sql/uninstall.php');

        return parent::uninstall();
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */
        if (((bool)Tools::isSubmit('submitReturntositeModule')) == true) {
            if ($this->_postValidation()) {
                $this->postProcess();
            }
            
        }

        $this->context->smarty->assign('module_dir', $this->_path);
        
        $output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');

        return $output.$this->renderForm();
    }
   
   
    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);

        $this->addJqueryUI('ui.datepicker');
        $this->addJS(_PS_JS_DIR_ . 'vendor/d3.v3.min.js');
    
        

        if ($this->access('edit') && $this->display == 'view') {
            $this->addJqueryPlugin('autocomplete');
        }
    }
    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitReturntositeModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
           
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );
       
       
        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        
        $products = Product::getProducts(1, 0, 0, 'id_product', 'ASC', false, true);
      
        
        foreach($products as $product){
            $query['id'] = $product['id_product'];
            $query['name'] = $product['name'];
            $product_query[] = $query;
            $query = [];
        }
       
        $allCategory = Category::getAllCategoriesName(
            null,
            null,
            true,
            null,
            true,
            '',
            'ORDER BY c.nleft, c.position'
        );
        foreach($allCategory as $category){
            $query['id_category'] = $category['id_category'];
            $query['name'] = $category['name'];
            $category_query[] = $query;
            $query = [];
        }
       
        return array(
            'tinymce' => true,
            'form' => array(
                'legend' => array(
                'title' => $this->l('Settings'),
                'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'textarea',
                        'label' => $this->l('Treść:'),
                        'name' => 'EP_DESCRIPTION',
                        'cols' => 40,
                        'rows' => 10,
                        'class' => 'rte',
                        'autoload_rte' => true,
                        'hint' => $this->l('Invalid characters:').' <>;=#{}',
                        'desc' => $this->l('Wprowadź treść i powiadom klientów, dla czego powinni pozostać na stronie/ dokończyć zakupy.')
                    ),
                    array(
                        'type' => 'color',
                        'label' => $this->l('Kolor:'),
                        'name' => 'EP_COLOR',
                        'desc' => $this->l('Ustaw główny kolor tła.') . '( "lightblue", "#CC6600")',
                    ),
                    array(
                        'type' => 'date',
                        'label' => $this->l('Aktywny od:'),
                        'name' => 'EP_ACTIVE_FROM',
                        'autoload_rte' => true,
                        'desc' => $this->l('Wybierz datę, od której popup będzie widoczny na stronie.'),
                    ),
                    array(
                        'type' => 'date',
                        'label' => $this->l('Aktywny do:'),
                        'name' => 'EP_ACTIVE_TO',
                        'autoload_rte' => true,
                        'desc' => $this->l('Wybierz datę, do której popup będzie widoczny na stronie.'),
                    ),
                     array(
                        'type' => 'select',
                        'cols' => 20,
                        'label' => $this->l('Kategoria:'),
                        'name' => 'EP_CATEGORY_2',
                        'options' => array(
                            'query' => $category_query,
                            'id' => 'id_category',
                            'name' => 'name',
                        ),
                        'desc' => $this->l('Wybierz produkty z listy.'),
                    ),
                    array(
                        'type' => 'select',
                        'cols' => 20,
                        'label' => $this->l('Produkt:'),
                        'name' => 'EP_SELECTED_PRODUCT',
                        'options' => array(
                            'query' => $product_query,
                            'id' => 'id',
                            'name' => 'name',
                        ),
                        'desc' => $this->l('Wybierz produkty z listy.'),
                    ),
                    
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Aktywny:'),
                        'name' => 'EP_ACTIVE',
                        'is_bool' => true,
                        'desc' => $this->l('Włącz/wyłącz exitpopup'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return array(
            'EP_DESCRIPTION'=> Configuration::get('EP_DESCRIPTION'),
            'EP_COLOR'=> Configuration::get('EP_COLOR'),
            'EP_ACTIVE_FROM'=> Configuration::get('EP_ACTIVE_FROM'),
            'EP_ACTIVE_TO' => Configuration::get('EP_ACTIVE_TO'),
            'EP_CATEGORY'=> Configuration::get('EP_CATEGORY'),
            'EP_CATEGORY_2'=> Configuration::get('EP_CATEGORY_2'),
            'EP_SELECTED_PRODUCT' => Configuration::get('EP_SELECTED_PRODUCT'),
            'EP_ACTIVE'=> Configuration::get('EP_ACTIVE'),
        );
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        Configuration::updateValue('EP_DESCRIPTION', Tools::getValue('EP_DESCRIPTION'), true);
        Configuration::updateValue('EP_COLOR', Tools::getValue('EP_COLOR'));
        Configuration::updateValue('EP_ACTIVE_FROM', Tools::getValue('EP_ACTIVE_FROM'));
        Configuration::updateValue('EP_ACTIVE_TO', Tools::getValue('EP_ACTIVE_TO'));
        Configuration::updateValue('EP_CATEGORY', Tools::getValue('EP_CATEGORY'));
        Configuration::updateValue('EP_CATEGORY_2', Tools::getValue('EP_CATEGORY_2'));
        Configuration::updateValue('EP_SELECTED_PRODUCT', Tools::getValue('EP_SELECTED_PRODUCT'));
        Configuration::updateValue('EP_ACTIVE', Tools::getValue('EP_ACTIVE'));
                
    }

    protected function _postValidation()
    {
        $errors = array();

        if(!Validate::isDate(Tools::getValue('EP_ACTIVE_FROM')) && !Validate::isDate(Tools::getValue('EP_ACTIVE_TO')) || (Tools::getValue('EP_ACTIVE_FROM')) >= (Tools::getValue('EP_ACTIVE_TO'))  ){
            $errors[] = $this->getTranslator()->trans('Data "Aktywny do" musi być większa od daty "Aktywny od".', array(), 'Modules.Imageslider.Admin');
        }
        if (count($errors)) {
            $this->context->smarty->assign('errors',implode('<br />', $errors) );
            return false;
        }

        /* Returns if validation is ok */

        return true;
    }

    
    /**
    * Add the CSS & JavaScript files you want to be loaded in the BO.
    */
    public function hookBackOfficeHeader()
    {
        if (Tools::getValue('module_name') == $this->name) {
            $this->context->controller->addJS($this->_path.'views/js/back.js');
            $this->context->controller->addCSS($this->_path.'views/css/back.css');
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookHeader()
    {
        $this->context->controller->addJS($this->_path.'/views/js/front.js');
        $this->context->controller->addCSS($this->_path.'/views/css/front.css');
       
        $this->context->controller->registerJavascript('fontawasome', 'https://kit.fontawesome.com/90be9a4e01.js',['server' => 'remote', 'position' => 'bottom', 'priority' => 150, 'crossorigin' => 'anonymous']);
    }

    public function hookDisplayFooterBefore()
    {
        function getContrastColor($hexColor) 
        {
            // hexColor RGB
            $R1 = hexdec(substr($hexColor, 1, 2));
            $G1 = hexdec(substr($hexColor, 3, 2));
            $B1 = hexdec(substr($hexColor, 5, 2));

            // Black RGB
            $blackColor = "#000000";
            $R2BlackColor = hexdec(substr($blackColor, 1, 2));
            $G2BlackColor = hexdec(substr($blackColor, 3, 2));
            $B2BlackColor = hexdec(substr($blackColor, 5, 2));

            // Calc contrast ratio
            $L1 = 0.2126 * pow($R1 / 255, 2.2) +
                0.7152 * pow($G1 / 255, 2.2) +
                0.0722 * pow($B1 / 255, 2.2);

            $L2 = 0.2126 * pow($R2BlackColor / 255, 2.2) +
                0.7152 * pow($G2BlackColor / 255, 2.2) +
                0.0722 * pow($B2BlackColor / 255, 2.2);

            $contrastRatio = 0;
            if ($L1 > $L2) {
                $contrastRatio = (int)(($L1 + 0.05) / ($L2 + 0.05));
            } else {
                $contrastRatio = (int)(($L2 + 0.05) / ($L1 + 0.05));
            }

            // If contrast is more than 5, return black color
            if ($contrastRatio > 5) {
                return '#000000';
            } else { 
                // if not, return white color.
                return '#FFFFFF';
            }
        }

        $text_color = getContrastColor(Configuration::get('EP_COLOR'));

        function isInDateRange(){
            $now = new DateTime(date("Y-m-d H:i:s"));
            $from = new DateTime(Configuration::get('EP_ACTIVE_FROM'));
            $to = new DateTime(Configuration::get('EP_ACTIVE_TO'));
        
        return($now >= $from && $now <= $to);           
        }

        if(isInDateRange() && Configuration::get('EP_ACTIVE')){
                $display_popup = true;
        }else{
                $display_popup = false;
        }
        
        /* Place your code here. */
        $this->smarty->assign([
            'ep_text' => Configuration::get('EP_DESCRIPTION'),
            'ep_main_color' =>Configuration::get('EP_COLOR'),
            'ep_text_color' => $text_color,
            'ep_category_id' => Configuration::get('EP_CATEGORY_2'),
            'ep_product_id' => Configuration::get('EP_SELECTED_PRODUCT'),
            'ep_active' => $display_popup,
        ]);

        return $this->fetch('module:returntosite/views/templates/hook/exitpopup.tpl');
    }
    

   /* public function hookDisplayLeftColumn()
    {
        function getContrastColor($hexColor) 
        {
            // hexColor RGB
            $R1 = hexdec(substr($hexColor, 1, 2));
            $G1 = hexdec(substr($hexColor, 3, 2));
            $B1 = hexdec(substr($hexColor, 5, 2));

            // Black RGB
            $blackColor = "#000000";
            $R2BlackColor = hexdec(substr($blackColor, 1, 2));
            $G2BlackColor = hexdec(substr($blackColor, 3, 2));
            $B2BlackColor = hexdec(substr($blackColor, 5, 2));

            // Calc contrast ratio
            $L1 = 0.2126 * pow($R1 / 255, 2.2) +
                0.7152 * pow($G1 / 255, 2.2) +
                0.0722 * pow($B1 / 255, 2.2);

            $L2 = 0.2126 * pow($R2BlackColor / 255, 2.2) +
                0.7152 * pow($G2BlackColor / 255, 2.2) +
                0.0722 * pow($B2BlackColor / 255, 2.2);

            $contrastRatio = 0;
            if ($L1 > $L2) {
                $contrastRatio = (int)(($L1 + 0.05) / ($L2 + 0.05));
            } else {
                $contrastRatio = (int)(($L2 + 0.05) / ($L1 + 0.05));
            }

            // If contrast is more than 5, return black color
            if ($contrastRatio > 5) {
                return '#000000';
            } else { 
                // if not, return white color.
                return '#FFFFFF';
            }
        }

        $text_color = getContrastColor(Configuration::get('EP_COLOR'));

        function isInDateRange(){
            $now = new DateTime(date("Y-m-d H:i:s"));
            $from = new DateTime(Configuration::get('EP_ACTIVE_FROM'));
            $to = new DateTime(Configuration::get('EP_ACTIVE_TO'));
        
        return($now >= $from && $now <= $to);           
        }

        if(isInDateRange() && Configuration::get('EP_ACTIVE')){
                $display_popup = true;
        }else{
                $display_popup = false;
        }
        
        $this->smarty->assign([
            'ep_text' => Configuration::get('EP_DESCRIPTION'),
            'ep_main_color' =>Configuration::get('EP_COLOR'),
            'ep_text_color' => $text_color,
            'ep_category_id' => Configuration::get('EP_CATEGORY_2'),
            'ep_product_id'=> Configuration::get('EP_SELECTED_PRODUCT'),
        ]);

        return $this->fetch('module:returntosite/views/templates/hook/exitpopup.tpl');
    }*/
}
