<?php

namespace Workspace\Jobs;

use Nexa\Queue\Job;

class TestEmailJob extends Job
{
    public function __construct($data = [])
    {
        parent::__construct($data);
    }

    public function handle()
    {
        // Job logic here
    }
}