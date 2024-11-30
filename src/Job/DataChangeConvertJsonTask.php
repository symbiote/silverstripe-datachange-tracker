<?php

namespace Symbiote\DataChange\Job;

use Symbiote\DataChange\Model\DataChangeRecord;

use SilverStripe\Dev\BuildTask;

/**
 * @author marcus
 */
class DataChangeConvertJsonTask extends BuildTask
{
    public function run($request)
    {
        if ($request->getVar('run')) {
            // load all items and convert 'before' and 'after' to json if their serialize returns a value
            $records = DataChangeRecord::get();
            foreach ($records as $record) {
                $before = @unserialize($record->Before);
                $after =  @unserialize($record->After);
                
                if ($before || $after) {
                    $record->Before = json_encode($before);
                    $record->After = json_encode($after);
                    $record->write();
                    echo "Updated $record->Title (#$record->ID)<br/>\n";
                }
            }
        }
    }
}
