<?php


namespace SV\RedisViewCounter;

use SV\RedisCache\Repository\Redis as RedisRepo;
use SV\StandardLib\Helper;
use XF\AddOn\AbstractSetup;
use XF\AddOn\StepRunnerInstallTrait;
use XF\AddOn\StepRunnerUninstallTrait;
use XF\AddOn\StepRunnerUpgradeTrait;

/**
 * Add-on installation, upgrade, and uninstall routines.
 */
class Setup extends AbstractSetup
{
    use StepRunnerInstallTrait;
    use StepRunnerUpgradeTrait;
    use StepRunnerUninstallTrait;

    public function checkRequirements(&$errors = [], &$warnings = []): void
    {
        parent::checkRequirements($errors, $warnings);

        $cache = Helper::isAddOnActive('SV/RedisCache') ? RedisRepo::get()->getRedisConnector() : null;
        if ($cache === null || !$cache->getCredis())
        {
            $warnings[] = 'This add-on requires Redis Cache to be installed and configured';
        }
    }
}
