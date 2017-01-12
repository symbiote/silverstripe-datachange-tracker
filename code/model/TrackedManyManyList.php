<?php

/**
 * A replacement manymany list that tracks add and remove calls
 *
 * @author marcus
 */
class TrackedManyManyList extends ManyManyList
{
    public $trackedRelationships = array();
    
    public function add($item, $extraFields = array())
    {
        $this->recordManyManyChange(__FUNCTION__, $item);
        return parent::add($item, $extraFields);
    }
    
    public function remove($item)
    {
        $this->recordManyManyChange(__FUNCTION__, $item);
        return parent::remove($item);
    }
    
    protected function recordManyManyChange($type, $item) {
        $joinName = $this->getJoinTable();
        if (!in_array($joinName, $this->trackedRelationships)) {
            return;
        }
        $parts = explode('_', $joinName);
        if (isset($parts[0]) && count($parts) > 1) {
            $addingToClass = $parts[0];
            $addingTo = $this->getForeignID();
            
            if (class_exists($addingToClass)) {
                $onItem = $addingToClass::get()->byID($addingTo);
                if ($onItem) {
                    if ($item && !($item instanceof DataObject)) {
                        $class = $this->dataClass;
                        $item = $class::get()->byID($item);
                    }
                    $join = $type == 'add' ? ' to ' : ' from ';
                    $type = ucfirst($type) . ' "' . $item->Title . '"' . $join . $parts[1];
                    $onItem->RelatedItem = $item->ClassName . ' #' . $item->ID;
                    singleton('DataChangeTrackService')->track($onItem, $type);
                }
            }
        }
    }
}
