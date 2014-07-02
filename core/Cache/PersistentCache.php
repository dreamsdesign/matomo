<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Cache;

use Piwik\CacheFile;
use Piwik\Development;
use Piwik\Piwik;
use Piwik\SettingsServer;

/**
 * Caching class used for static caching.
 */
class PersistentCache
{
    /**
     * @var CacheFile
     */
    private static $storage = null;
    private static $content = null;
    private static $isDirty = false;

    private $cacheKey;

    public function __construct($cacheKey)
    {
        $this->cacheKey = $cacheKey;

        if (is_null(self::$content)) {
            self::$content = array();
            self::populateCache();
        }
    }

    public function setCacheKey($cacheKey)
    {
        $this->cacheKey = $cacheKey;
    }

    public function get()
    {
        return self::$content[$this->cacheKey];
    }

    public function has()
    {
        return array_key_exists($this->cacheKey, self::$content);
    }

    public function set($content)
    {
        self::$content[$this->cacheKey] = $content;
        self::$isDirty = true;
    }

    private static function populateCache()
    {
        if (Development::isEnabled()) {
            return;
        }

        if (SettingsServer::isTrackerApiRequest()) {
            $eventToPersist = 'Tracker.end';
            $mode           = 'tracker';
        } else {
            $eventToPersist = 'Request.dispatch.end';
            $mode           = 'ui';
        }

        $cache = self::getStorage()->get('StaticCache-' . $mode);

        if (is_array($cache)) {
            self::$content = $cache;
        }

        Piwik::addAction($eventToPersist, array(__CLASS__, 'persistCache'));
    }

    public static function persistCache()
    {
        if (self::$isDirty) {
            if (SettingsServer::isTrackerApiRequest()) {
                $mode = 'tracker';
            } else {
                $mode = 'ui';
            }

            self::getStorage()->set('StaticCache-' . $mode, self::$content);
        }
    }

    /**
     * @return CacheFile
     */
    private static function getStorage()
    {
        if (is_null(self::$storage)) {
            self::$storage = new CacheFile('tracker', 43200);
        }

        return self::$storage;
    }
}
