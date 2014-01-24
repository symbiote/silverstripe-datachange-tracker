<?php

/**
 * Add to classes you want changes recorded for
 *
 * @author marcus@silverstripe.com.au
 * @license BSD License http://silverstripe.org/bsd-license/
 */
class ChangeRecordable extends DataObjectDecorator {
	
	public function onBeforeWrite() {
		parent::onBeforeWrite();
		DataChangeRecord::track($this->owner);
	}
}
