<?php

/**
 * Plugin to test the ILIAS logging interface. ilCronHookPlugin is used as a slot here
 * because of the minimum requirements of these plugins.
 */
use ILIAS\Cron\CronHookPlugin;

class ilLoggingTestPlugin extends CronHookPlugin
{
    public function getPluginName():string
    {
        return "LogginTest";
    }

    public function getCronJobInstances(): array
    {
        return [];
    }

    public function getCronJobInstance($jobId): ilCronJob
    {
        throw new \LogicException(
            "This plugin does not actually provide any cron jobs."
        );
    }
}
