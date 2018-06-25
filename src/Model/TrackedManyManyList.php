<?php

namespace Symbiote\DataChange\Model;

use SilverStripe\ORM\ManyManyList;
use SilverStripe\ORM\DataObject;

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
        $result = parent::add($item, $extraFields);
        return $result;
    }

    public function remove($item)
    {
        $this->recordManyManyChange(__FUNCTION__, $item);
        $result = parent::remove($item);
        return $result;
    }

    protected function recordManyManyChange($type, $item)
    {
        $joinName = $this->getJoinTable();
        if (!in_array($joinName, $this->trackedRelationships)) {
            return;
        }
        $parts = explode('_', $joinName);
        if (isset($parts[0]) && count($parts) > 1) {
            // table name could be sometihng like Symbiote_DataChange_Tests_TestObject_Kids
            // which is ClassName_RelName, with
            $tableName = $parts;
            $relationName = array_pop($tableName);
            $tableName = implode('_', $tableName);

            $addingToClass = $this->tableClass($tableName);
            if (!$addingToClass) {
                return;
            }
            if (!class_exists($addingToClass)) {
                return;
            }
            $onItem = $addingToClass::get()->byID($this->getForeignID());
            if (!$onItem) {
                return;
            }
            if ($item && !($item instanceof DataObject)) {
                $class = $this->dataClass();
                $item = $class::get()->byID($item);
            }
            $join = $type === 'add' ? ' to ' : ' from ';
            $type = ucfirst($type) . ' "' . $item->Title . '"' . $join . $relationName;
            $onItem->RelatedItem = $item->ClassName . ' #' . $item->ID;
            singleton('DataChangeTrackService')->track($onItem, $type);
        }
    }

    /**
     * Find the class for the given table.
     *
     * Stripped down version from framework that does not attempt to strip _Live and _versions postfixes as
     * that throws errors in its preg_match(). (At least it did as of 2018-06-22 on SilverStripe 4.1.1)
     *
     * @param string $table
     * @return string|null The FQN of the class, or null if not found
     */
    private function tableClass($table)
    {
        $tables = DataObject::getSchema()->getTableNames();
        $class = array_search($table, $tables, true);
        if ($class) {
            return $class;
        }
        return null;
    }
}
