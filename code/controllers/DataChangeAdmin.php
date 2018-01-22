<?php

/**
 * @author marcus@symbiote.com.au
 * @license BSD License http://silverstripe.org/bsd-license/
 */
class DataChangeAdmin extends ModelAdmin
{
    public static $managed_models = array(
        'DataChangeRecord',
    );

    public static $url_segment = 'datachanges';
    public static $menu_title = 'Data Changes';

    public function getEditForm($id = null, $fields = null)
    {
        $form = parent::getEditForm($id);
        $gridField = $form->Fields()->fieldByName($this->modelClass);
        $gridFieldConfig = $gridField->getConfig();
        $gridFieldConfig->removeComponentsByType('GridFieldAddNewButton');

        return $form;
    }
}
