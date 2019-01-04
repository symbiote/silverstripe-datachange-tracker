<?php

namespace Symbiote\DataChange\Job;

use SilverStripe\ORM\Queries\SQLDelete;
use SilverStripe\Core\Injector\Injector;
use Symbiote\QueuedJobs\Services\QueuedJobService;
use Symbiote\QueuedJobs\Services\AbstractQueuedJob;
use Symbiote\DataChange\Model\DataChangeRecord;

if (!class_exists(AbstractQueuedJob::class)) {
    return;
}

/**
 * A scheduled regular prune of _old_ data change records
 *
 * @author marcus
 */
class PruneChangesBeforeJob extends AbstractQueuedJob
{

    public function __construct($priorTo = null)
    {
        $ts = 0;
        if ($priorTo) {
            $ts = strtotime($priorTo);
        }
        if ($ts <= 0) {
            $ts = time() - 90 * 86400;
        }
        $this->priorTo = $priorTo;
        $this->pruneBefore = date('Y-m-d 00:00:00', $ts);
            // NOTE(Jake): 2018-05-08
            //
            // Change steps to 1 as it's technically doing
            // this in 1 step now, this is to avoid an issue where
            // totalSteps=0 can occur and the job won't requeue itself.
            // (When using ->count() off the DataList)
            //
        $this->totalSteps = 1;
    }

    public function getSignature()
    {
        return md5($this->pruneBefore);
    }

    public function getTitle()
    {
        return "Prune data change track entries before " . $this->pruneBefore;
    }

    public function process()
    {
        $items = DataChangeRecord::get()->filter('Created:LessThan', $this->pruneBefore);
        $max = $items->max('ID');

        $query = new SQLDelete('DataChangeRecord', '"ID" < \'' . $max . '\'');
        $query->execute();

        $job = new PruneChangesBeforeJob($this->priorTo);

        $next = date('Y-m-d 03:00:00', strtotime('tomorrow'));

        $this->currentStep = 1;
        $this->isComplete = true;

        Injector::inst()->get(QueuedJobService::class)->queueJob($job, $next);
    }
}

