<?php

namespace Symbiote\DataChange\Job;

use Symbiote\DataChange\Model\DataChangeRecord;

use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\Queries\SQLDelete;

/**
 *
 *
 * @author <marcus@symbiote.com.au>
 * @license BSD License http://www.silverstripe.org/bsd-license
 */
class CleanupDataChangeHistoryTask extends BuildTask
{
    public function run($request)
    {
        $confirm = $request->getVar('run') ? true : false;
        $force = $request->getVar('force') ? true : false;
        $since = $request->getVar('older');
        
        if (!$since) {
            echo "Please specify an 'older' param with a date older than which to prune (in strtotime friendly format)<br/>\n";
            return;
        }
        
        $since = strtotime((string) $since);
        if (!$since) {
            echo "Please specify an 'older' param with a date older than which to prune (in strtotime friendly format)<br/>\n";
            return;
        }

        if ($since > strtotime('-3 months') && !$force) {
            echo "To cleanup data more recent than 3 months, please supply the 'force' parameter as well as the run parameter, swapping to dry run <br/>\n";
            $confirm = false;
        }
        
        $since = date('Y-m-d H:i:s', $since);
        
        $items = DataChangeRecord::get()->filter('Created:LessThan', $since);
        $max = $items->max('ID');
        echo "Pruning records older than $since (ID $max)<br/>\n";
        
        if ($confirm && $max) {
            $query = new SQLDelete('DataChangeRecord', '"ID" < \'' . $max . '\'');
            $query->execute();
        } else {
            echo "Dry run performed, please supply the run=1 parameter to actually execute the deletion!<br/>\n";
        }
    }
}
