<?php

namespace Breyta;

interface ProgressInterface
{
    /**
     * Output information about starting the migration process
     *
     * Info contains:
     *  - `migrations` - an array of class names
     *  - `count` - an integer how many migrations are going to be executed
     *
     * @param \stdClass $info
     */
    public function start(\stdClass $info);
}
