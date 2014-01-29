<?php

/**
 * Add to classes you want changes recorded for
 *
 * @author marcus@silverstripe.com.au
 * @license BSD License http://silverstripe.org/bsd-license/
 */
class ChangeRecordable extends DataExtension {
	
	private static $ignored_fields = array();

	public function onBeforeWrite() {
		parent::onBeforeWrite();
		DataChangeRecord::track($this->owner);
	}

	public function getIgnoredFields(){
		$ignored = Config::inst()->get('ChangeRecordable', 'ignored_fields');
		$class = $this->owner->ClassName;
		if(isset($ignored[$class])) {
			return array_combine($ignored[$class], $ignored[$class]);
		}
	}
}
