<?php

/**
 * Record a change to a dataobject; use this to track data changes of objects
 *
 * @author marcus@silverstripe.com.au
 * @license BSD License http://silverstripe.org/bsd-license/
 */
class DataChangeRecord extends DataObject {
	private static $db = array(
		'ChangeType'		=> 'Varchar',
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
		'GetVars'			=> 'Text',
		'PostVars'			=> 'Text',
	);

	private static $has_one = array(
		'ChangedBy'			=> 'Member',
	);

	private static $summary_fields = array(
		'ChangeType'		=> 'Change Type',
		'ClassType'		 	=> 'Record Class',
		'ClassID' 			=> 'Record ID',
		'ObjectTitle'		=> 'Record Title',
		'ChangedBy.Title' 	=> 'User',
		'Created'			=> 'Modification Date',
	);

	private static $searchable_fields = array(
		'ChangeType',
		'ObjectTitle',
		'ClassType',
		'ClassID',
	);

	private static $indexes = array(
		'ClassID_Type' => '("ClassID","ClassType")',
	);

	private static $default_sort = 'Created DESC';

	/**
	 * Should request variables be saved too?
	 *
	 * @var boolean
	 */
	private static $save_request_vars = false;

	private static $field_blacklist = array('Password');

	private static $request_vars_blacklist = array('url', 'SecurityID');

	public function getCMSFields($params = null) {
		Requirements::css(DATACHANGE_PATH . '/css/datachange-tracker.css');

		$fields = FieldList::create(
			ToggleCompositeField::create('Details', 'Details', array(
				ReadonlyField::create('ChangeType', 'Type of change'),
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
				ReadonlyField::create('GetVars'),
				ReadonlyField::create('PostVars'),
			))->setStartClosed(false)->addExtraClass('datachange-field')
		);

		if (strlen($this->Before)) {
			$before = Object::create($this->ClassType, json_decode($this->Before, true), true);
			$after 	= Object::create($this->ClassType, json_decode($this->After, true), true);
			$diff 	= DataDifferencer::create($before, $after);
			$diffed = $diff->diffedData();
			$diffText = '';

			$changedFields = array();
			foreach ($diffed->toMap() as $field => $prop) {
				if (is_object($prop)) {
					continue;
				}
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
	public function track(DataObject $changedObject, $type = 'Change') {
		$changes = $changedObject->getChangedFields(true, 2);
		if (count($changes)) {
			// remove any changes to ignored fields
			$ignored = $changedObject->getIgnoredFields();
			if($ignored){
				$changes = array_diff_key($changes, $ignored);
				foreach ($ignored as $ignore) {
					if (isset($changes[$ignore])) {
						unset($changes[$ignore]);
					}
				}
			}
		}

		foreach (self::config()->field_blacklist as $key) {
			if (isset($changes[$key])) {
				unset($changes[$key]);
			}
		}

		if ((empty($changes) && $type == 'Change')
			|| ($type === 'Delete' && Versioned::get_reading_mode() === 'Stage.Live')
		) {
			return;
		}

		$this->ChangeType = $type;
		$this->ClassType = $changedObject->ClassName;
		$this->ClassID = $changedObject->ID;
		// @TODO this will cause issue for objects without titles
		$this->ObjectTitle = $changedObject->Title;
		$this->Stage = Versioned::get_reading_mode();

		$before = array();
		$after = array();

		if($type != 'Change' && $type != 'New'){ // If we are (un)publishing we want to store the entire object
			$before = ($type === 'Unpublish') ? $changedObject->toMap() : null;
			$after = ($type === 'Publish') ? $changedObject->toMap() : null;
		} else { // Else we're tracking the changes to the object
			foreach ($changes as $field => $change) {
				if ($field == 'SecurityID') {
					continue;
				}
				$before[$field] = $change['before'];
				$after[$field] = $change['after'];
			}
		}

		if($this->Before && is_array($before)) {
			//merge the old array last to keep it's value as we want keep the earliest version of each field
			$this->Before = json_encode(array_replace(json_decode($this->Before, true), $before));
		} else {
			$this->Before = json_encode($before);
		}
		if ($this->After && is_array($after)) {
			//merge the new array last to keep it's value as we want the newest version of each field
			$this->After = json_encode(array_replace($after, json_decode($this->After, true)));
		} else {
			$this->After = json_encode($after);
		}

		if (self::config()->save_request_vars) {

			foreach (self::config()->request_vars_blacklist as $key) {
				unset($_GET[$key]);
				unset($_POST[$key]);
			}

			$this->GetVars = isset($_GET) ? json_encode($_GET) : null;
			$this->PostVars = isset($_POST) ? json_encode($_POST) : null;
		}

		$this->ChangedByID = Member::currentUserID();

		if (Member::currentUserID() && Member::currentUser()) {
			$this->CurrentEmail = Member::currentUser()->Email;
		}

		if (isset($_SERVER['SERVER_NAME'])) {
			$protocol = 'http';
			$protocol = isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on" ? 'https://' : 'http://';

			$this->CurrentURL = $protocol . $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
		} else if (Director::is_cli()) {
			$this->CurrentURL = 'CLI';
		} else {
			$this->CurrentURL = 'Could not determine current URL';
		}

		$this->RemoteIP = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : (Director::is_cli() ? 'CLI' : 'Unknown remote addr');
		$this->Referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
		$this->Agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';

		$this->write();
		return $this;
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
