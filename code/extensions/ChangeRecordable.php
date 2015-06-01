<?php

/**
 * Add to classes you want changes recorded for
 *
 * @author marcus@silverstripe.com.au
 * @license BSD License http://silverstripe.org/bsd-license/
 */
class ChangeRecordable extends DataExtension {
	
	private static $ignored_fields = array();
	
	protected $isNewObject = FALSE;
	protected $changeType = 'Change';

	public function onBeforeWrite() {
		parent::onBeforeWrite();
		if($this->owner->isInDB()) {
			DataChangeTrackService::track($this->owner, $this->changeType);
		} else {
			$this->isNewObject = TRUE;
			$this->changeType = 'New';
		}
	}

	public function onAfterWrite() {
		parent::onAfterWrite();
		if($this->isNewObject) {
			DataChangeTrackService::track($this->owner, $this->changeType);
			$this->isNewObject = FALSE;
		}
	}
		
	public function onBeforeDelete() {
		parent::onBeforeDelete();
		DataChangeTrackService::track($this->owner, 'Delete');
	}

	public function getIgnoredFields(){
		$ignored = Config::inst()->get('ChangeRecordable', 'ignored_fields');
		$class = $this->owner->ClassName;
		if(isset($ignored[$class])) {
			return array_combine($ignored[$class], $ignored[$class]);
		}
	}
}