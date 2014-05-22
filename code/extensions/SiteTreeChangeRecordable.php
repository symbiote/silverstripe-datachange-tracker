<?php

/**
 * Add to Pages you want changes recorded for
 * 
 * Note this class duplicates code from ChangeRecordable as it needs to inherit from SiteTreeExtension
 *
 * @author stephen@silverstripe.com.au
 * @license BSD License http://silverstripe.org/bsd-license/
 */
class SiteTreeChangeRecordable extends SiteTreeExtension {

	private static $ignored_fields = array();

	public function onBeforeWrite() {
		parent::onBeforeWrite();
		DataChangeRecord::track($this->owner);
	}
	
	public function onBeforeDelete() {
		parent::onBeforeDelete();
		DataChangeRecord::track($this->owner, 'Delete');
	}
	
	public function onAfterPublish(&$original) {
		parent::onAfterPublish($original);
		DataChangeRecord::track($this->owner, 'Publish');
	}

	public function onAfterUnpublish() {
		parent::onAfterUnpublish();
		DataChangeRecord::track($this->owner, 'Unpublish');
	}

	public function getIgnoredFields(){
		$ignored = Config::inst()->get('ChangeRecordable', 'ignored_fields');
		$class = $this->owner->ClassName;
		if(isset($ignored[$class])) {
			return array_combine($ignored[$class], $ignored[$class]);
		}
	}
	
	public function updateCMSFields(FieldList $fields) {
		//Get all data changes relating to this page filter them by publish/unpublish
		$dataChanges = DataChangeRecord::get()->filter('ClassID', $this->owner->ID)->exclude('ChangeType', 'Change');
		//create a gridfield out of them
		$gridFieldConfig = GridFieldConfig_RecordViewer::create();
		$publishedGrid = new GridField('PublishStates', 'Published States', $dataChanges, $gridFieldConfig);
		$dataColumns = $publishedGrid->getConfig()->getComponentByType('GridFieldDataColumns');
		$dataColumns->setDisplayFields(array('ChangeType'			=> 'Change Type',
												'ObjectTitle'		=> 'Page Title',
												'ChangedBy.Title' 	=> 'User',
												'Created'			=> 'Modification Date',
						));

		//linking through to the datachanges modeladmin
		
		$fields->addFieldsToTab('Root.PublishedState', $publishedGrid);
		return $fields;
	}
}
