<?php

/**
 * Description of DataChangeTrackService
 *
 * @author Stephen McMahon <stephen@silverstripe.com.au>
 */
class DataChangeTrackService {

	protected $dcr_cache = array();

	public function track(DataObject $object, $type = 'Change') {

		if (!isset($this->dcr_cache["{$object->ID}-{$object->Classname}"])) {
			$this->dcr_cache["{$object->ID}-{$object->Classname}"] = DataChangeRecord::create();
		}
		
		$this->dcr_cache["{$object->ID}-{$object->Classname}"]->track($object, $type);
	}
	
	public function __toString() {
		return '';
	}
}
