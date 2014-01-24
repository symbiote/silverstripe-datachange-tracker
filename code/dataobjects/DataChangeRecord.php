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
		'ObjectTitle'		=> 'Varchar(255)',
		'ClassID'			=> 'Int',
		'Before'			=> 'Text',
		'After'				=> 'Text',
		'Stage'				=> 'Text',
		'CurrentEmail'		=> 'Text',
		'CurrentURL'		=> 'Varchar(255)',
		'RemoteIP'			=> 'Varchar(128)',
		'Referer'			=> 'Varchar(255)',
		'Agent'				=> 'Varchar(255)',
	);

	public static $has_one = array(
		'ChangedBy'			=> 'Member',
	);

	public static $summary_fields = array(
		'ClassID' => 'Item ID',
		'ClassType' => 'Class',
		'ObjectTitle' => 'Title',
		'ChangedBy.Title' => 'User',
		'Created'		=> 'Modified',
	);
	
	public static $searchable_fields = array(
		'ObjectTitle',
		'ClassType',
		'ClassID',
	);
	
	public static $default_sort = 'Created DESC';
	
	public function getCMSFields($params = null) {
		$fields = parent::getCMSFields($params);
		
		$fields->addFieldToTab('Root.Main', new ReadonlyField('Created', 'Created'), 'ClassType');
		
//		$fields->replaceField('Before', new ReadonlyField('Before'));
//		$fields->replaceField('After', new ReadonlyField('After'));
		
		if (strlen($this->Before)) {
			$before = new DataObject(unserialize($this->Before));
			$after = new DataObject(unserialize($this->After));
			$diff = new DataDifferencer($before, $after);
			$diffed = $diff->diffedData();
			$diffText = '';

			$fields->addFieldToTab('Root.Main', new HeaderField('FieldChanges', 'Changed Fields'));
			
			foreach ($diffed->getAllFields() as $field => $prop) {
				$fields->addFieldToTab('Root.Main', new TextField('ChangedField'.$field, $field, $prop));
			}
		}

		$fields = $fields->makeReadonly();

		return $fields;
	}
	
	public static function track(DataObject $changedObject) {
		$record = new DataChangeRecord();
		$record->ClassType = $changedObject->ClassName;
		$record->ClassID = $changedObject->ID;
		$record->ObjectTitle = $changedObject->Title;
		$changes = $changedObject->getChangedFields();
		
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
	
	public function canDelete($member = null) {
		return false;
	}
}
