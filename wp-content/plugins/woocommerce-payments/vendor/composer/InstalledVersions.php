<?php











namespace Composer;

use Composer\Autoload\ClassLoader;
use Composer\Semver\VersionParser;






class InstalledVersions
{
private static $installed = array (
  'root' => 
  array (
    'pretty_version' => 'dev-trunk',
    'version' => 'dev-trunk',
    'aliases' => 
    array (
    ),
    'reference' => '4e94b12d70e5d6db46771de34fc6f98f750e217f',
    'name' => 'woocommerce/payments',
  ),
  'versions' => 
  array (
    'automattic/jetpack-a8c-mc-stats' => 
    array (
      'pretty_version' => 'v1.4.9',
      'version' => '1.4.9.0',
      'aliases' => 
      array (
      ),
      'reference' => '6920ecb008891558cbf619838d1ce963838f077d',
    ),
    'automattic/jetpack-assets' => 
    array (
      'pretty_version' => 'v1.11.10',
      'version' => '1.11.10.0',
      'aliases' => 
      array (
      ),
      'reference' => '9f16b45fd6c20af87012bd091d29652540dd5273',
    ),
    'automattic/jetpack-autoloader' => 
    array (
      'pretty_version' => 'v2.10.10',
      'version' => '2.10.10.0',
      'aliases' => 
      array (
      ),
      'reference' => '8060f5c5822671b146ce8f57380193c8fb8e2571',
    ),
    'automattic/jetpack-config' => 
    array (
      'pretty_version' => 'v1.5.3',
      'version' => '1.5.3.0',
      'aliases' => 
      array (
      ),
      'reference' => '3526c284d3b845d57275fe1f64fbb2c71649b16e',
    ),
    'automattic/jetpack-connection' => 
    array (
      'pretty_version' => 'v1.30.13',
      'version' => '1.30.13.0',
      'aliases' => 
      array (
      ),
      'reference' => 'b6cb5e87ffe1dca5260307066f720f8ddcfebda3',
    ),
    'automattic/jetpack-constants' => 
    array (
      'pretty_version' => 'v1.6.11',
      'version' => '1.6.11.0',
      'aliases' => 
      array (
      ),
      'reference' => '96edcfa7e81d30a72e6c7f926dec37874821f963',
    ),
    'automattic/jetpack-heartbeat' => 
    array (
      'pretty_version' => 'v1.3.13',
      'version' => '1.3.13.0',
      'aliases' => 
      array (
      ),
      'reference' => 'cce7b6910a3ee2ad7bad3c8fea3905774f9198df',
    ),
    'automattic/jetpack-options' => 
    array (
      'pretty_version' => 'v1.13.4',
      'version' => '1.13.4.0',
      'aliases' => 
      array (
      ),
      'reference' => 'aa021e07d60b978f8631de1aa564af80b46eede5',
    ),
    'automattic/jetpack-redirect' => 
    array (
      'pretty_version' => 'v1.7.6',
      'version' => '1.7.6.0',
      'aliases' => 
      array (
      ),
      'reference' => '98205592cc7d921c4f78b8f2e337b49b3f838715',
    ),
    'automattic/jetpack-roles' => 
    array (
      'pretty_version' => 'v1.4.10',
      'version' => '1.4.10.0',
      'aliases' => 
      array (
      ),
      'reference' => '5141d944757818a3b8669c0e303f665ac44def87',
    ),
    'automattic/jetpack-status' => 
    array (
      'pretty_version' => 'v1.9.1',
      'version' => '1.9.1.0',
      'aliases' => 
      array (
      ),
      'reference' => 'fc8fee93fe822c202d29394f6576f3586d26f861',
    ),
    'automattic/jetpack-terms-of-service' => 
    array (
      'pretty_version' => 'v1.9.14',
      'version' => '1.9.14.0',
      'aliases' => 
      array (
      ),
      'reference' => '34cce155f69a47881d94754f14b203f31dc02d09',
    ),
    'automattic/jetpack-tracking' => 
    array (
      'pretty_version' => 'v1.13.15',
      'version' => '1.13.15.0',
      'aliases' => 
      array (
      ),
      'reference' => '33fbe299417771e5a6e1ce98209a9cb7443b1eff',
    ),
    'composer/installers' => 
    array (
      'pretty_version' => 'v1.10.0',
      'version' => '1.10.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '1a0357fccad9d1cc1ea0c9a05b8847fbccccb78d',
    ),
    'myclabs/php-enum' => 
    array (
      'pretty_version' => '1.7.7',
      'version' => '1.7.7.0',
      'aliases' => 
      array (
      ),
      'reference' => 'd178027d1e679832db9f38248fcc7200647dc2b7',
    ),
    'roundcube/plugin-installer' => 
    array (
      'replaced' => 
      array (
        0 => '*',
      ),
    ),
    'shama/baton' => 
    array (
      'replaced' => 
      array (
        0 => '*',
      ),
    ),
    'woocommerce/payments' => 
    array (
      'pretty_version' => 'dev-trunk',
      'version' => 'dev-trunk',
      'aliases' => 
      array (
      ),
      'reference' => '4e94b12d70e5d6db46771de34fc6f98f750e217f',
    ),
    'woocommerce/subscriptions-core' => 
    array (
      'pretty_version' => '1.1.0',
      'version' => '1.1.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '0bae09c4e64617a23a6d8706883e0e345f449ad6',
    ),
  ),
);
private static $canGetVendors;
private static $installedByVendor = array();







public static function getInstalledPackages()
{
$packages = array();
foreach (self::getInstalled() as $installed) {
$packages[] = array_keys($installed['versions']);
}


if (1 === \count($packages)) {
return $packages[0];
}

return array_keys(array_flip(\call_user_func_array('array_merge', $packages)));
}









public static function isInstalled($packageName)
{
foreach (self::getInstalled() as $installed) {
if (isset($installed['versions'][$packageName])) {
return true;
}
}

return false;
}














public static function satisfies(VersionParser $parser, $packageName, $constraint)
{
$constraint = $parser->parseConstraints($constraint);
$provided = $parser->parseConstraints(self::getVersionRanges($packageName));

return $provided->matches($constraint);
}










public static function getVersionRanges($packageName)
{
foreach (self::getInstalled() as $installed) {
if (!isset($installed['versions'][$packageName])) {
continue;
}

$ranges = array();
if (isset($installed['versions'][$packageName]['pretty_version'])) {
$ranges[] = $installed['versions'][$packageName]['pretty_version'];
}
if (array_key_exists('aliases', $installed['versions'][$packageName])) {
$ranges = array_merge($ranges, $installed['versions'][$packageName]['aliases']);
}
if (array_key_exists('replaced', $installed['versions'][$packageName])) {
$ranges = array_merge($ranges, $installed['versions'][$packageName]['replaced']);
}
if (array_key_exists('provided', $installed['versions'][$packageName])) {
$ranges = array_merge($ranges, $installed['versions'][$packageName]['provided']);
}

return implode(' || ', $ranges);
}

throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
}





public static function getVersion($packageName)
{
foreach (self::getInstalled() as $installed) {
if (!isset($installed['versions'][$packageName])) {
continue;
}

if (!isset($installed['versions'][$packageName]['version'])) {
return null;
}

return $installed['versions'][$packageName]['version'];
}

throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
}





public static function getPrettyVersion($packageName)
{
foreach (self::getInstalled() as $installed) {
if (!isset($installed['versions'][$packageName])) {
continue;
}

if (!isset($installed['versions'][$packageName]['pretty_version'])) {
return null;
}

return $installed['versions'][$packageName]['pretty_version'];
}

throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
}





public static function getReference($packageName)
{
foreach (self::getInstalled() as $installed) {
if (!isset($installed['versions'][$packageName])) {
continue;
}

if (!isset($installed['versions'][$packageName]['reference'])) {
return null;
}

return $installed['versions'][$packageName]['reference'];
}

throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
}





public static function getRootPackage()
{
$installed = self::getInstalled();

return $installed[0]['root'];
}







public static function getRawData()
{
return self::$installed;
}



















public static function reload($data)
{
self::$installed = $data;
self::$installedByVendor = array();
}




private static function getInstalled()
{
if (null === self::$canGetVendors) {
self::$canGetVendors = method_exists('Composer\Autoload\ClassLoader', 'getRegisteredLoaders');
}

$installed = array();

if (self::$canGetVendors) {
foreach (ClassLoader::getRegisteredLoaders() as $vendorDir => $loader) {
if (isset(self::$installedByVendor[$vendorDir])) {
$installed[] = self::$installedByVendor[$vendorDir];
} elseif (is_file($vendorDir.'/composer/installed.php')) {
$installed[] = self::$installedByVendor[$vendorDir] = require $vendorDir.'/composer/installed.php';
}
}
}

$installed[] = self::$installed;

return $installed;
}
}
