<?php

namespace Symbiote\DataChange\Admin;

use SilverStripe\Admin\ModelAdmin;

/**
 * @author marcus@symbiote.com.au
 * @license BSD License http://silverstripe.org/bsd-license/
 */
class DataChangeAdmin extends ModelAdmin
{
    private static $managed_models = array(
    'DataChangeRecord',
    );

    private static $url_segment = 'datachanges';
    private static $menu_title = 'Data Changes';
}
