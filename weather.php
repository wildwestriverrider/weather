<?php

if (!defined('_PS_VERSION_')) {
	exit;
}

require __DIR__.'/vendor/autoload.php';

class Weather extends Module
{
	public function __construct()
	{
		$this->name = 'weather';
		$this->author = 'James Sweeney';
		$this->tab = 'front_office_features';
		$this->version = '1.0.0';

		$this->bootstrap = true;
		parent::__construct();

		$this->displayName = $this->l('Weather');
		$this->description = $this->l('Display weather for a given zip code.');
		$this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
	}

	public function getContent()
	{
		$output = null;

		if (Tools::isSubmit('submit'.$this->name))
		{
			$zip_code = strval(Tools::getValue('zip_code'));
			if (!$zip_code || empty($zip_code))
				$output .= $this->displayError($this->l('Invalid Zip Code value'));
			else
			{
				$this->updateZipCode($zip_code);
				$output .= $this->displayConfirmation($this->l('Settings updated'));
			}
		}
		return $output.$this->displayForm();
	}

	public function displayForm()
	{
		// Get default language
		$default_lang = (int)Configuration::get('PS_LANG_DEFAULT');

		// Init Fields form array
		$fields_form[0]['form'] = array(
			'legend' => array(
				'title' => $this->l('Weather Settings'),
			),
			'input' => array(
				array(
					'type' => 'text',
					'label' => $this->l('Zip Code'),
					'name' => 'zip_code',
					'size' => 10,
					'required' => true
				)
			),
			'submit' => array(
				'title' => $this->l('Save'),
				'class' => 'btn btn-default pull-right'
			)
		);

		$helper = new HelperForm();

		// Module, token and currentIndex
		$helper->module = $this;
		$helper->name_controller = $this->name;
		$helper->token = Tools::getAdminTokenLite('AdminModules');
		$helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;

		// Language
		$helper->default_form_language = $default_lang;
		$helper->allow_employee_form_lang = $default_lang;

		// Title and action
		$helper->title = $this->displayName;
		$helper->submit_action = 'submit'.$this->name;

		// Retrieve the zip code from the db
		$sql = 'SELECT zip_code FROM '._DB_PREFIX_.'weather ORDER BY id_weather LIMIT 1';
		$zip_code = Db::getInstance()->executeS($sql);
		$zip_code = $zip_code[0]['zip_code'];
		// Load current value
		$helper->fields_value['zip_code'] = $zip_code;

		return $helper->generateForm($fields_form);
	}

	public function hookDisplayLeftColumn($params)
	{
		$dotenv = new Dotenv\Dotenv(__DIR__);
		$dotenv->load();
		$open_weather_key = getenv('OPEN_WEATHER_KEY');
		$sql = 'SELECT zip_code FROM '._DB_PREFIX_.'weather ORDER BY id_weather LIMIT 1';
		$zip_code = Db::getInstance()->executeS($sql);
		$zip_code = $zip_code[0]['zip_code'];
		$data = file_get_contents("http://api.openweathermap.org/data/2.5/weather?zip={$zip_code},us&appid={$open_weather_key}&units=imperial");
		$data = json_decode($data);
		$this->context->smarty->assign(
			array(
				'zip_code' => $zip_code,
				'my_module_link' => $this->context->link->getModuleLink('weather', 'display'),
				'data' => $data,
				'temp' => round($data->main->temp, 1),
				'temp_min' => round($data->main->temp_min, 1),
				'temp_max' => round($data->main->temp_max, 1),
			)
		);
		return $this->display(__FILE__, 'views/hook/weather.tpl');
	}

	public function hookDisplayHeader()
	{
		$this->context->controller->addCSS($this->_path.'css/weather.css', 'all');
	}

	public function install()
	{
		if (
			!parent::install() ||
			!$this->registerHook('leftColumn') ||
			!$this->registerHook('header') ||
			!$this->installDb()
		)
			return false;

		return true;
	}

	public function installDb()
	{
		$query = 'CREATE TABLE IF NOT EXISTS '._DB_PREFIX_.'weather (
			id_weather INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
			zip_code VARCHAR( 10 ) NOT NULL
		) ENGINE = '._MYSQL_ENGINE_.' CHARACTER SET utf8 COLLATE utf8_general_ci;';

		return (Db::getInstance()->execute($query));
	}

	public function uninstall()
	{
		if (!parent::uninstall() || !$this->uninstallDb())
			return false;

		return true;
	}

	protected function uninstallDb()
	{
		Db::getInstance()->execute('DROP TABLE '._DB_PREFIX_.'weather');
		return true;
	}

	protected function updateZipCode($zip_code)
	{
		// Check if a zip_code already exists in the db since we are only working with one
		$sql = 'SELECT count(*) FROM '._DB_PREFIX_.'weather';
		$count = Db::getInstance()->getValue($sql);
		if($count > 0)
			Db::getInstance()->execute('UPDATE '._DB_PREFIX_.'weather SET zip_code = '.$zip_code.' WHERE id_weather = 1');
		else
			Db::getInstance()->execute('INSERT INTO '._DB_PREFIX_.'weather (zip_code) VALUES ('.$zip_code.')');
	}
}