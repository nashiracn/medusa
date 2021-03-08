<?php
/**
 * @copyright 2013 Sébastien Armand
 * @license http://opensource.org/licenses/MIT MIT
 */

namespace Khepin\Medusa;


/**
 * Finds all the dependencies on which a given package relies
 */
class DependencyResolver
{
    protected $package;
    static ?NewDependencyResolver $dependency_resolver = null;

    public function __construct($package)
    {
        $this->package = $package;
        if (is_null(self::$dependency_resolver)) {
            self::$dependency_resolver = new NewDependencyResolver();
        }
    }

    public function resolve()
    {
        $deps = array($this->package);
        $resolved = array();

        while (count($deps) > 0) {
            $package = array_pop($deps);
            $package = $this->rename($package);

            if (!$package || $this->isSystemPackage($package) === true) {
                continue;
            }

            $package_name = $package;
            $package = self::$dependency_resolver->package($package_name);

            if (!is_null($package)) {
                foreach ($package as $version) {
                    if (!isset($version['require'])) {
                        continue;
                    }

                    foreach ($version['require'] as $dependency => $version) {
                        if ($this->skipped($dependency)) {
                            continue;
                        }
                        if (!in_array($dependency, $resolved) && !in_array($dependency, $deps)) {
                            $deps[] = $dependency;
                            $deps = array_unique($deps);
                        }
                    }
                }

                $resolved[] = $package_name;
            }
        }

        return $resolved;
    }

    private function isSystemPackage($package) {
        // If the package name don't contain a "/" we will skip it here.
        // In a composer.json in the require / require-dev part you normally add packages
        // you depend on. A package name follows the format "vendor/package".
        // E.g. symfony/console
        // You can put other dependencies in here as well like `php` or `ext-zip`.
        // Those dependencies will be skipped (because they don`t have a vendor ;)).
        // The reason is simple: If you try to request the package "php" at packagist
        // you won`t get a JSON response with information we expect.
        // You will get valid HTML of the packagist search.
        // To avoid those errors and to save API calls we skip dependencies without a vendor.
        //
        // This follows the documentation as well:
        //
        // 	The package name consists of a vendor name and the project's name.
        // 	Often these will be identical - the vendor name just exists to prevent naming clashes.
        //	Source: https://getcomposer.org/doc/01-basic-usage.md
        return (strstr($package, '/')) ? false: true;
    }

    private function skipped($package): bool {
        return in_array($package, [
            'deployer/phpseclib',
            'phpseclib/phpseclib',
            'elfet/php-ssh',
        ]);
    }

    private function rename($package)
    {
        static $packages = array(
            'facebook/php-webdriver' => 'instaclick/php-webdriver',
            'metadata/metadata' => 'jms/metadata',
            'symfony/doctrine-bundle' => 'doctrine/doctrine-bundle',
            'symfony/translator' => 'symfony/translation',
            'willdurand/expose-translation-bundle' => 'willdurand/js-translation-bundle',

            // obsolete
            'zendframework/zend-registry' => null,
            
            // some older phpdocumentor version require these
            'zendframework/zend-translator' => null,
            'zendframework/zend-locale' => null,
            'phpdocumentor/template-installer' => null,
            'pear-symfony/eventdispatcher' => null
        );

        if (array_key_exists($package, $packages)) {
            return $packages[$package];
        }

        return $package;
    }
}
