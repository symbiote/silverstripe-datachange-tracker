<?php

/**
 * @author marcus@symbiote.com.au
 * @license BSD License http://silverstripe.org/bsd-license/
 */
class DataChangeAdmin extends ModelAdmin {
	public static $managed_models = array(
		'DataChangeRecord',
	);

	public static $url_segment = 'datachanges';
	public static $menu_title = 'Data Changes';

}
