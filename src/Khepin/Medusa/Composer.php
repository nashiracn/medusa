<?php

namespace Zxedu\Composer;

use Composer\Console\Application;
use Composer\Package\CompletePackageInterface;

class Composer
{
    static $composer = null;

    public function __construct()
    {
        if (is_null(self::$composer)) {
            ini_set('memory_limit', 1024 * 1024 * 1536);

            $application = new Application();
            self::$composer = $application->getComposer();
        }
    }

    /**
     * @param string $packageName
     * @return CompletePackageInterface[]
     */
    public function getProvides(string $packageName): array
    {
        foreach (self::$composer->getRepositoryManager()->getRepositories() as $repo) {
            if ($repo instanceof \Composer\Repository\ComposerRepository) {
                /** @var \Composer\Package\CompletePackage $c */
                return $repo->whatProvides(new \Composer\DependencyResolver\Pool('dev'), $packageName, true);
            }
        }
        return [];
    }
}
