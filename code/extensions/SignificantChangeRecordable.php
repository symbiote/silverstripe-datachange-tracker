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
		if ($this->owner->LastSignificantChange !== NULL) {

				$dateTime = new DateTime($this->owner->LastSignificantChange);
				//Put these fields on the top of the First Tab's form
				$fields->first()->Tabs()->first()->getChildren()->unshift(
					LabelField::create("infoLastSignificantChange", "<strong>This EP will now appear in Search and EP"
						. " subscription emails spent out tonight. To stop this click the checkbox and save this EP"
						. " again.</strong>"
					)
			);
			$fields->insertAfter(
					CheckboxField::create("isSignificantChange",
							"CLEAR Last Significant change: {$dateTime->Format('d/m/Y H:i')}"
						)->setDescription(
							'Check and save this Environment Plan again to clear the Last Significant change date.'
						)
					, 'infoLastSignificantChange'
				);
				$fields->insertAfter(
				TextField::create('ChangeDescription', 'Description of Changes')
						->setDescription('This is the next that will appear to emails, to explain what has changed.')
				, 'isSignificantChange'
			);
		}
	}

	public function onBeforeWrite() {
		parent::onBeforeWrite();
		// Load the significant_fields and check to see if they have changed if they have record the current DateTime
		$significant = Config::inst()->get($this->owner->Classname, 'significant_fields');

		$isSignicantChange = $this->owner->isSignificantChange;

		if(isset($significant) && !$isSignicantChange){
			$significant = array_combine($significant, $significant);

			//If the owner object or an extension of it implements getSignificantChange call it instead of testing here
			if ($this->owner->hasMethod('getSignificantChange') && $this->owner->getSignificantChange()) {
				//Set LastSignificantChange to now
				$this->owner->LastSignificantChange = date(DateTime::ATOM);
				$this->owner->isSignificantChange = true;
			} else {
				$changes = $this->owner->getChangedFields(true, 2);
				//A simple interesect of the keys gives us whether a change has occurred
				if (count($changes) && count(array_intersect_key($changes, $significant))) {
					//Set LastSignificantChange to now
					$this->owner->LastSignificantChange = date(DateTime::ATOM);
					$this->owner->isSignificantChange = true;
				}
			}
			//If we don't have any significant changes leave the field alone as a previous edit may have been
			//significant.
		} else {
			if($this->owner->isInDB) $this->owner->LastSignificantChange = NULL;
		}
	}

}