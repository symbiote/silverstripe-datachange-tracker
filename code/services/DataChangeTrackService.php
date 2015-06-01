<?php

/**
 * Description of DataChangeTrackService
 *
 * @author Stephen McMahon <stephen@silverstripe.com.au>
 */
class DataChangeTrackService {

	protected static $dcr_cache = array();
	
	public static function cache($ID, $classname) {
		if (isset(self::$dcr_cache["{$ID}-{$classname}"])) {
			return self::$dcr_cache["{$ID}-{$classname}"]; 
		}
		
		return self::$dcr_cache["{$ID}-{$classname}"] = DataChangeRecord::create();
	}
	
	public static function track(DataObject $object, $type = 'Change') {
		self::cache($object->ID, $object->Classname);
		
		self::$dcr_cache["{$object->ID}-{$object->Classname}"]->track($object, $type);
	}
}
