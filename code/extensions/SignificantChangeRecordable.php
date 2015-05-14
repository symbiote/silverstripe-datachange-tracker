<?php

/**
 * Add to classes you want to track specfic changes on
 *
 * @author stephen@silverstripe.com.au
 * @license BSD License http://silverstripe.org/bsd-license/
 */
class SignificantChangeRecordable extends DataExtension {

	private static $ignored_fields = array();

	private static $significant_fields = array();

	public static $db = array(
        'LastSignificantChange' => 'SS_Datetime',
		'ChangeDescription' => 'Text'
    );

	public function updateCMSFields(FieldList $fields) {
		$fields->removeByName('LastSignificantChange');
		$fields->removeByName('ChangeDescription');
		$fields->insertBefore(
			CheckboxField::create("isSignificantChange", "Significant change: {$this->owner->LastSignificantChange}")
				->setDescription(($this->owner->LastSignificantChange != NULL)
					? 'Last Significant: ' . $this->owner->LastSignificantChange
					: 'Is this a Significant change?')
				->setValue(TRUE)
			, 'Reference'
		);
		$fields->insertAfter(
			TextField::create('ChangeDescription', 'Description of Changes')
			, 'isSignificantChange'
		);
	}

	public function onBeforeWrite() {
		parent::onBeforeWrite();
		// Load the significant_fields and check to see if they have changed if they have record the current DateTime
		$significant = Config::inst()->get($this->owner->Classname, 'significant_fields');
		
		$isSignicantChange = $this->owner->isSignificantChange;
		
		if(isset($significant) && $isSignicantChange){
			$significant = array_combine($significant, $significant);
			
			//If the owner object or an extension of it implements getSignificantChange call it instead of testing here
			if ($this->owner->hasMethod('getSignificantChange') && $this->owner->getSignificantChange()) {
				//Set LastSignificantChange to now
				$this->owner->LastSignificantChange = date(DateTime::ATOM);
			} else {
				$changes = $this->owner->getChangedFields(true, 2);
				//A simple interesect of the keys gives us whether a change has occurred
				if (count($changes) && count(array_intersect_key($changes, $significant))) {
					//Set LastSignificantChange to now
					$this->owner->LastSignificantChange = date(DateTime::ATOM);
				}
			}
			//If we don't have any significant changes leave the field alone as a previous edit may have been 
			//significant.
		} else {
			$this->owner->LastSignificantChange = NULL;
		}
	}

}