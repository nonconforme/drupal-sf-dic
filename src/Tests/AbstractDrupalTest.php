<?php

namespace MakinaCorpus\Drupal\Sf\Container\Tests;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Please be aware that when working with this base class, everything you do
 * you actually do in the real Drupal site, there is no environment isolation
 * so it will mess up with your data.
 */
abstract class AbstractDrupalTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Is a Drupal instance bootstrapped
     *
     * @var bool
     */
    static private $bootstrapped = false;

    /**
     * A database connection object from Drupal
     *
     * @var mixed
     */
    static private $databaseConnection;

    /**
     * _drupal_bootstrap_configuration() override
     */
    static private function bootstrapConfiguration()
    {
        if (!isset($_SERVER['HTTP_HOST'])) {
            $_SERVER['HTTP_HOST'] = '127.0.0.1';
        }
        if (!isset($_SERVER['HTTP_REFERER'])) {
            $_SERVER['HTTP_REFERER'] = '';
        }
        if (!isset($_SERVER['SERVER_PROTOCOL']) || ($_SERVER['SERVER_PROTOCOL'] != 'HTTP/1.0' && $_SERVER['SERVER_PROTOCOL'] != 'HTTP/1.1')) {
            $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.0';
        }
        if (!isset($_SERVER['REMOTE_ADDR'])) {
            $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        }

        //drupal_settings_initialize();
    }

    /**
     * Find if a Drupal instance is configured for testing and bootstrap it if
     * found.
     *
     * @return \DatabaseConnection
     */
    static private function findDrupalDatabaseConnection()
    {
        if (self::$bootstrapped) {
            return self::$databaseConnection;
        }

        $variableName = 'DRUPAL_PATH';

        // Try to find out the right site root.
        $directory = null;
        if (isset($GLOBALS[$variableName])) {
            $directory = $GLOBALS[$variableName];
        } else {
            throw new \RuntimeException(sprintf("You must configure the %s environment or phpunit variable", $variableName));
        }

        if (!is_dir($directory)) {
            throw new \RuntimeException(sprintf("%s: directory does not exists", $directory));
        }
        if (!file_exists($directory . '/index.php')) {
            throw new \RuntimeException(sprintf("%s: directory is not a PHP application directory", $directory));
        }

        $bootstrapInc = $directory . '/includes/bootstrap.inc';
        if (!is_file($bootstrapInc)) {
            throw new \RuntimeException(sprintf("%s: is a not a Drupal installation or version mismatch", $directory));
        }

        if (!$handle = fopen($bootstrapInc, 'r')) {
            throw new \RuntimeException(sprintf("%s: cannot open for reading", $bootstrapInc));
        }

        $buffer = fread($handle, 512);
        fclose($handle);

        $matches = [];
        if (preg_match("/^\s*define\('VERSION', '([^']+)'/ims", $buffer, $matches)) {
            list($parsedMajor) = explode('.', $matches[1]);
        }
        if (!isset($parsedMajor) || empty($parsedMajor)) {
            throw(sprintf("%s: could not parse core version", $bootstrapInc));
            return null;
        }

        // We are OK to go
        define('DRUPAL_ROOT', $directory);
        require_once $bootstrapInc;

        self::bootstrapConfiguration();
        self::$bootstrapped = true;

        \Drupal::_toggleTestMode(true);

        drupal_bootstrap(DRUPAL_BOOTSTRAP_DATABASE);

        return self::$databaseConnection = \Database::getConnection();
    }

    /**
     * @var \ModuleHandlerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $nullModuleHandler;

    /**
     * @var \DrupalCacheInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $nullLegacyCache;

    /**
     * @var CacheBackendInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $nullCacheBackend;

    /**
     * @var \DatabaseConnection
     */
    private $db;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var \stdClass[]
     */
    private $accounts = [];

    /**
     * Create a Drupal user
     *
     * @param string[] $permissionList
     *   Permission string list
     *
     * @return \stdClass
     */
    protected function createDrupalUser($permissionList = [])
    {
        $account = new \stdClass();
        $this->accounts[] = $account;
        $stupidHash = uniqid() . mt_rand();
        $account->name = $stupidHash;
        $account->mail = $stupidHash . '@example.com';
        $account->roles = [];
        user_save($account);

        // Fake user access cache for testing
        $data = &drupal_static('user_access');
        $data[$account->uid] = array_combine($permissionList, $permissionList);

        return $account;
    }

    /**
     * Get Drupal anonymous user
     *
     * @return \stdClass
     */
    final protected function getAnonymousUser()
    {
        return drupal_anonymous_user();
    }

    /**
     * @return ModuleHandlerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    final protected function getNullModuleHandler()
    {
        if (!$this->nullModuleHandler) {
            $this->nullModuleHandler = $this->getMock('\Drupal\Core\Extension\ModuleHandlerInterface');
        }

        return $this->nullModuleHandler;
    }

    /**
     * @return CacheBackendInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    final protected function getNullCacheBackend()
    {
        if (!$this->nullCacheBackend) {
            $this->nullCacheBackend = $this->getMock('\Drupal\Core\Cache\CacheBackendInterface');
        }

        return $this->nullCacheBackend;
    }

    /**
     * @return \DrupalCacheInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    final protected function getNullLegacyCache()
    {
        if (!$this->nullLegacyCache) {
            $this->nullLegacyCache = $this->getMock('\DrupalCacheInterface');
        }

        return $this->nullLegacyCache;
    }

    /**
     * Get current Drupal site database connection
     *
     * @return \DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $this->db;
    }

    /**
     * Get current Drupal site service container
     *
     * @return ContainerInterface
     */
    protected function getDrupalContainer()
    {
        drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

        \Drupal::_init();

        return \Drupal::getContainer();
    }

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->db = self::findDrupalDatabaseConnection();

        // @todo
        //   - create temporary container
        //   - create connection on temporary database
        //   - pseudo a minimal site
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        foreach ($this->accounts as $account) {
            user_delete($account->uid);
        }

        \Drupal::unsetContainer();

        unset($this->nullCacheBackend, $this->nullLegacyCache, $this->nullModuleHandler);

        parent::tearDown();
    }
}