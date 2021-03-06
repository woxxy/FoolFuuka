<?php

class Controller_Admin_Preferences extends Controller_Admin
{

	public function before()
	{
		parent::before();
		// set controller title
		$this->_views['controller_title'] = '<a href="' . Uri::Create("admin/preferences") . '">' . __("Preferences") . '</a>';
	}


	function action_theme()
	{
		$this->_views["method_title"] = __("Theme");

		$form = array();

		$form['open'] = array(
			'type' => 'open'
		);

		// build the array for the form
		$form['ff.gen.site_title'] = array(
			'type' => 'input',
			'label' => 'Title',
			'class' => 'span3',
			'placeholder' => FOOL_GEN_WEBSITE_TITLE,
			'preferences' => TRUE,
			'validate' => 'trim|max_length[32]',
			'help' => __('Sets the title of your site.')
		);

		// build the array for the form
		$form['ff.gen.index_title'] = array(
			'type' => 'input',
			'label' => 'Index title',
			'class' => 'span3',
			'placeholder' => FOOL_GEN_INDEX_TITLE,
			'preferences' => TRUE,
			'validate' => 'trim|max_length[32]',
			'help' => __('Sets the title displayed in the index page.')
		);
		/*
		$form['ff_lang_default'] = array(
			'type' => 'dropdown',
			'label' => __('Default language'),
			'help' => __('The language the users will see as they reach your site.'),
			'options' => config_item('ff_available_languages'),
			'default_value' => FOOL_LANG_DEFAULT,
			'preferences' => TRUE,
		);
		*/
		$form['separator-2'] = array(
			'type' => 'separator'
		);

		$themes = array();
		$theme_obj = new Theme();

		foreach($theme_obj->get_all() as $name => $theme)
		{
			$themes[] = array(
				'type' => 'checkbox',
				'label' => $theme['name'] . ' theme',
				'help' => sprintf(__('Enable %s theme'), $theme['name']),
				'array_key' => $name,
				'preferences' => TRUE,
				'checked' => defined('FOOL_PREF_THEMES_THEME_' . strtoupper($name) . '_ENABLED') ?
					constant('FOOL_PREF_THEMES_THEME_' . strtoupper($name) . '_ENABLED'):0
			);
		}

		$form['ff.theme.active_themes'] = array(
			'type' => 'checkbox_array',
			'label' => __('Active themes'),
			'help' => __('Choose the themes to make available to the users. Admins are able to access any of them even if disabled.'),
			'checkboxes' => $themes
		);

		$themes_default = array();

		foreach($theme_obj->get_all() as $name => $theme)
		{
			$themes_default[$name] = $theme['name'];
		}

		$form['ff.theme.default'] = array(
			'type' => 'select',
			'label' => __('Default theme'),
			'help' => __('The theme the users will see as they reach your site.'),
			'options' => $themes_default,
			'default_value' => FOOL_THEME_DEFAULT,
			'preferences' => TRUE,
		);

		$form['ff.theme.google_analytics'] = array(
			'type' => 'input',
			'label' => __('Google Analytics code'),
			'placeholder' => 'UX-XXXXXXX-X',
			'preferences' => TRUE,
			'help' => __("Insert your Google Analytics code to get statistics."),
			'class' => 'span2'
		);

		$form['separator-3'] = array(
			'type' => 'separator'
		);

		$form['ff.theme.header_text'] = array(
			'type' => 'textarea',
			'label' => __('Header Text ("notices")'),
			'preferences' => TRUE,
			'help' => __("Inserts the text above in the header, below the nagivation links."),
			'class' => 'span5'
		);

		$form['ff.theme.header_code'] = array(
			'type' => 'textarea',
			'label' => __('Header Code'),
			'preferences' => TRUE,
			'help' => __("This will insert the HTML code inside the &lt;HEAD&gt;."),
			'class' => 'span5'
		);

		$form['ff.theme.footer_text'] = array(
			'type' => 'textarea',
			'label' => __('Footer Text'),
			'preferences' => TRUE,
			'help' => __('Credits in the footer and similar.'),
			'class' => 'span5'
		);

		$form['ff.theme.footer_code'] = array(
			'type' => 'textarea',
			'label' => __('Footer Code'),
			'preferences' => TRUE,
			'help' => __("This will insert the HTML code above after the &lt;BODY&gt;."),
			'class' => 'span5'
		);

		$form['separator'] = array(
			'type' => 'separator'
		);

		$form['submit'] = array(
			'type' => 'submit',
			'value' => __('Submit'),
			'class' => 'btn btn-primary'
		);

		$form['close'] = array(
			'type' => 'close'
		);


		$data['form'] = $form;

		// create a form
		$this->_views["main_content_view"] = View::forge('admin/form_creator', $data);
		return Response::forge(View::forge('admin/default', $this->_views));
	}


	function action_advertising()
	{
		$this->_views["method_title"] = __("Advertising");

		$form = array();

		$form['open'] = array(
			'type' => 'open'
		);

		$form['ff.ads_top_banner'] = array(
			'type' => 'textarea',
			'label' => __('Top banner'),
			'help' => __('Insert the HTML code provided by your advertiser.'),
			'preferences' => TRUE,
			'validation' => 'trim',
			'class' => 'span5'
		);

		$form['ff.ads_top_banner_active'] = array(
			'type' => 'checkbox',
			'preferences' => TRUE,
			'help' => __('Enable top banner')
		);

		$form['ff.ads_bottom_banner'] = array(
			'type' => 'textarea',
			'label' => __('Bottom banner'),
			'help' => __('Insert the HTML code provided by your advertiser.'),
			'preferences' => TRUE,
			'validation' => 'trim',
			'class' => 'span5'
		);

		$form['ff.ads_bottom_banner_active'] = array(
			'type' => 'checkbox',
			'preferences' => TRUE,
			'help' => __('Enable bottom banner')
		);

		$form['separator'] = array(
			'type' => 'separator'
		);

		$form['submit'] = array(
			'type' => 'submit',
			'value' => __('Submit'),
			'class' => 'btn btn-primary'
		);

		$form['close'] = array(
			'type' => 'close'
		);


		$data['form'] = $form;

		// create a form
		$this->_views["main_content_view"] = View::forge('admin/form_creator', $data);
		return Response::forge(View::forge('admin/default', $this->_views));
	}


	function action_registration()
	{
		$this->_views["method_title"] = __("Registration");

		$form = array();

		$form['open'] = array(
			'type' => 'open'
		);

		$form['ff.reg_disabled'] = array(
			'type' => 'checkbox',
			'preferences' => TRUE,
			'help' => __('Disable New User Registrations')
		);
		$form['ff.reg_email_disabled'] = array(
			'type' => 'checkbox',
			'preferences' => TRUE,
			'help' => __('Disable Email Activation')
		);

		$form['separator'] = array(
			'type' => 'separator'
		);

		$form['paragraph'] = array(
			'type' => 'paragraph',
			'help' => __('In order to use reCAPTCHA&trade; you need to sign up for the service at <a href="http://www.google.com/recaptcha">reCAPTCHA&trade;</a>, which will provide you with a public and a private key.')
		);

		$form['ff.reg_recaptcha_public'] = array(
			'type' => 'input',
			'label' => __('reCaptcha&trade; Public Key'),
			'preferences' => TRUE,
			'help' => __('Insert the public key provided by reCAPTCHA&trade;.'),
			'validation' => 'trim',
			'class' => 'span4'
		);

		$form['ff.reg_recaptcha_secret'] = array(
			'type' => 'input',
			'label' => __('reCaptcha&trade; Secret Key'),
			'preferences' => TRUE,
			'help' => __('Insert the private key provided by reCAPTCHA&trade;.'),
			'validation' => 'trim',
			'class' => 'span4'
		);

		$form['separator-2'] = array(
			'type' => 'separator'
		);

		$form['submit'] = array(
			'type' => 'submit',
			'value' => __('Submit'),
			'class' => 'btn btn-primary'
		);

		$form['close'] = array(
			'type' => 'close'
		);


		$data['form'] = $form;

		// create a form
		$this->_views["main_content_view"] = View::forge('admin/form_creator', $data);
		return Response::forge(View::forge('admin/default', $this->_views));
	}

}

/* end of file preferences.php */