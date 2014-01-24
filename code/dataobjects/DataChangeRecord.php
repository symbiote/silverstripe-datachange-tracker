<?php

/**
 * Record a change to a dataobject; use this to track data changes of objects 
 *
 * @author marcus@silverstripe.com.au
 * @license BSD License http://silverstripe.org/bsd-license/
 */
class DataChangeRecord extends DataObject {
	public static $db = array(
		'ClassType'			=> 'Varchar',
		'ClassID'			=> 'Int',
		'ObjectTitle'		=> 'Varchar(255)',
		'Before'			=> 'Text',
		'After'				=> 'Text',
		'Stage'				=> 'Text',
		'CurrentEmail'		=> 'Text',
		'CurrentURL'		=> 'Varchar(255)',
		'Referer'			=> 'Varchar(255)',
		'RemoteIP'			=> 'Varchar(128)',
		'Agent'				=> 'Varchar(255)',
	);

	public static $has_one = array(
		'ChangedBy'			=> 'Member',
	);

	public static $summary_fields = array(
		'ClassType'		 	=> 'Record Class',
		'ClassID' 			=> 'Record ID',
		'ObjectTitle'		=> 'Record Title',
		'ChangedBy.Title' 	=> 'User',
		'Created'			=> 'Modification Date',
	);
	
	public static $searchable_fields = array(
		'ObjectTitle',
		'ClassType',
		'ClassID',
	);
	
	public static $default_sort = 'Created DESC';
	
	public function getCMSFields($params = null) {
		Requirements::css(DATACHANGE_PATH . '/css/datachange-tracker.css');
		
		$fields = FieldList::create(
			ToggleCompositeField::create('Details', 'Details', array(
				ReadonlyField::create('ClassType', 'Record Class'),
				ReadonlyField::create('ClassID', 'Record ID'),
				ReadonlyField::create('ObjectTitle', 'Record Title'),
				ReadonlyField::create('Created', 'Modification Date'),
				ReadonlyField::create('Stage', 'Stage'),
				ReadonlyField::create('User', 'User', $this->getMemberDetails()),
				ReadonlyField::create('CurrentURL', 'URL'),
				ReadonlyField::create('Referer', 'Referer'),
				ReadonlyField::create('RemoteIP', 'Remote IP'),
				ReadonlyField::create('Agent', 'Agent'),
			))->setStartClosed(false)->addExtraClass('datachange-field'),
			ToggleCompositeField::create('RawData', 'Raw Data', array(
				ReadonlyField::create('Before'),
				ReadonlyField::create('After'),
			))->addExtraClass('datachange-field')
		);
		
		if (strlen($this->Before)) {
			$before = Object::create($this->ClassType, unserialize($this->Before), true);
			$after 	= Object::create($this->ClassType, unserialize($this->After), true);
			$diff 	= DataDifferencer::create($before, $after);
			$diffed = $diff->diffedData();
			$diffText = '';

			$changedFields = array();
			foreach ($diffed->toMap() as $field => $prop) {
				$changedFields[] = $readOnly = ReadonlyField::create('ChangedField' . $field, $field, $prop);
				$readOnly->dontEscape = true;
				$readOnly->addExtraClass('datachange-field');
			}

			$fields->insertBefore(
				ToggleCompositeField::create('FieldChanges', 'Changed Fields', $changedFields)
				->setStartClosed(false)
				->addExtraClass('datachange-field'),
				'RawData'
			);
		}

		$fields = $fields->makeReadonly();

		return $fields;
	}
	

	/**
	 * Track a change to a DataObject
	 * @return DataChangeRecord
	 **/
	public static function track(DataObject $changedObject) {
		$changes = $changedObject->getChangedFields(true, 2);
		
		if (empty($changes)) {
			return;
		}

		$record = DataChangeRecord::create();
		$record->ClassType = $changedObject->ClassName;
		$record->ClassID = $changedObject->ID;
		$record->ObjectTitle = $changedObject->Title;
		
		
		$before = array();
		$after = array();
		
		foreach ($changes as $field => $change) {
			$before[$field] = $change['before'];
			$after[$field] = $change['after'];
		}
		
		$record->Before = serialize($before);
		$record->After = serialize($after);
		
		$record->ChangedByID = Member::currentUserID();
		
		if (Member::currentUserID() && Member::currentUser()) {
			$record->CurrentEmail = Member::currentUser()->Email;
		}
		
		if (isset($_SERVER['SERVER_NAME'])) {
			$protocol = 'http';
			$protocol = isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on" ? 'https://' : 'http://';

			$record->CurrentURL = $protocol . $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
		} else if (Director::is_cli()) {
			$record->CurrentURL = 'CLI';
		} else {
			$record->CurrentURL = 'Could not determine current URL';
		}

		$record->RemoteIP = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : (Director::is_cli() ? 'CLI' : 'Unknown remote addr'); 
		$record->Referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
		$record->Agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
		
		$s = Versioned::get_reading_mode();
		$record->Stage = Versioned::get_reading_mode();
		
		$record->write();
		return $record;
	}
	

	/**
	 * @return boolean
	 **/
	public function canDelete($member = null) {
		return false;
	}


	/**
	 * @return string
	 **/
	public function getTitle(){
		return $this->ClassType . ' #' . $this->ClassID;
	}


	/**
	 * Return a description/summary of the user
	 * @return string
	 **/
	public function getMemberDetails(){
		if($user = $this->ChangedBy()){
			$name = $user->getTitle();
			if($user->Email){
				$name .= " <$user->Email>";
			}
			return $name;
		}
	}
}
