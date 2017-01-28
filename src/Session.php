<?php
namespace Apatis\Session;

use Apatis\ArrayStorage\CollectionSerializable;
use Apatis\CoreIntercept\CallableFunction;
use Apatis\Exceptions\InvalidArgumentException;
use Apatis\Session\Flash\Current;
use Apatis\Session\Flash\Next;
use Apatis\Session\Flash\Prev;

/**
 * Class Session
 * @package Apatis\Session
 */
class Session
{
    /**
     * Session Disabled Constant
     * @uses @const PHP_SESSION_DISABLED
     */
    const SESSION_DISABLED = 0;

    /**
     * Session None Constant
     * @uses @const PHP_SESSION_NONE
     */
    const SESSION_NONE     = 1;

    /**
     * Session Active Constant
     * @uses @const PHP_SESSION_ACTIVE
     */
    const SESSION_ACTIVE   = 2;

    /**
     * Constant For Default Cookie Parameter Key
     * @see session_set_cookie_params()
     * @link http://php.net/manual/en/function.session-set-cookie-params.php
     */
    const PARAM_LIFETIME = 'lifetime';
    const PARAM_DOMAIN   = 'domain';
    const PARAM_PATH     = 'path';
    const PARAM_SECURE   = 'secure';
    const PARAM_HTTP_ONLY= 'httponly';

    /**
     * String Constant Collection
     * @override able
     */
    const FLASH_NEXT = Next::class;
    const FLASH_PREV = Prev::class;
    const FLASH_CURRENT = Current::class;

    /**
     * Cookie Params
     *
     * @var array
     */
    protected $cookieParams = [
        /**
         * Default Values
         */
        Session::PARAM_LIFETIME  => 0,
        Session::PARAM_PATH      => '/',
        Session::PARAM_DOMAIN    => '',
        Session::PARAM_SECURE    => false,
        Session::PARAM_HTTP_ONLY => false,
    ];

    /**
     * Stored Cookies
     *
     * @var array
     */
    protected $cookies = [];

    /**
     * @var callable
     */
    protected $deleteCookieInstance;

    /**
     * Determine if flash has been moved
     *
     * @var bool
     */
    protected $flashMoved = false;

    /**
     * Intercept to call function
     *
     * @var CallableFunction
     */
    protected $intercept;

    public function __construct(
        SegmentFactory $segmentFactory,
        TokenFactory $tokenFactory,
        CallableFunction $intercept = null,
        array $cookies = null,
        $delete_cookie = null
    ) {
        $this->segment_factory    = $segmentFactory;
        $this->csrf_token_factory = $tokenFactory;
        $this->cookies            = is_null($cookies) ? $_COOKIE : $cookies;

        $this->intercept = $intercept ?: new CallableFunction();
        $this->setDeleteCookieInstance($delete_cookie);
        /**
         * Set Cookie Params Default
         */
        $this->setCookieParams(
            $this->intercept->call('session_get_cookie_params')
        );
    }

    /**
     * Check If session is Started
     *
     * @return bool
     */
    public function isStarted()
    {
        $started = ($this->sessionStatus() === Session::SESSION_ACTIVE);

        // if the session was started externally, move the flash values forward
        if ($started && ! $this->flashMoved) {
            $this->moveFlash();
        }

        return $started;
    }

    /**
     * Check The Session Status
     *
     * @see session_status()
     * @link http://php.net/manual/en/function.session-status.php
     *
     * @return int
     */
    public function sessionStatus()
    {
        /**
         * If Session extension is Not Loaded,
         * This mean the Session is Disabled
         */
        if (! $this->intercept->call('extension_loaded', 'session')) {
            return Session::SESSION_DISABLED;
        }

        if ($this->intercept->call('function_exist', 'session_status')) {
            return $this->intercept->call('session_status');
        }

        if ($this->intercept->call('function_exist', 'ini_set')) {
            $log      = 'log_errors';
            $trans    = 'session.use_trans_sid';

            // get setting of log errors
            $current_log = $this->intercept->call('ini_get', $log);
            $this->intercept->call('ini_set', $log, 0);

            $current    = $this->intercept->call('ini_get', $trans);
            $last_level = $this->intercept->call('error_reporting', 0);
            $result     = $this->intercept->call('ini_set', $trans, $current);

            // re set again
            $this->intercept->call('ini_set', $log, $current_log);
            $this->intercept->call('error_reporting', $last_level);

            return $result !== $current
                ? Session::SESSION_ACTIVE
                : Session::SESSION_NONE;
        }

        return Session::SESSION_NONE;
    }

    /**
     * Move the flash for :
     * NEXT to CURRENT and CURRENT TO PREV
     * Thereby Clearing The Next Value
     */
    protected function moveFlash()
    {
        if (! isset($_SESSION[static::FLASH_NEXT])
            || ! $_SESSION[static::FLASH_NEXT] instanceof Next
        ) {
            /**
             * @var CollectionSerializable $_SESSION[]
             */
            $next = $_SESSION[static::FLASH_NEXT] instanceof CollectionSerializable
                ? $_SESSION[static::FLASH_NEXT]->all()
                : [];
            $_SESSION[static::FLASH_NEXT] = new Next($next);
        }

        if (! isset($_SESSION[static::FLASH_CURRENT])
            || ! $_SESSION[static::FLASH_CURRENT] instanceof Current
        ) {
            /**
             * @var CollectionSerializable $_SESSION[]
             */
            $current = $_SESSION[static::FLASH_CURRENT] instanceof CollectionSerializable
                ? $_SESSION[static::FLASH_CURRENT]->all()
                : [];
            $_SESSION[static::FLASH_CURRENT] = new Current($current);
        }

        /**
         * @var CollectionSerializable $_SESSION[]
         */
        $_SESSION[static::FLASH_PREV]    = new Prev($_SESSION[static::FLASH_CURRENT]->all());
        $_SESSION[static::FLASH_CURRENT] = new Current($_SESSION[static::FLASH_NEXT]->all());
        $_SESSION[static::FLASH_NEXT]    = new Next();
        $this->flashMoved = true;
    }

    /**
     * Clears all session variables across all segments.
     */
    public function clear()
    {
        $this->intercept->call('session_unset');
    }

    /**
     * Writes session data from all segments and ends the session.
     *
     * @see session_write_close()
     * @link http://php.net/manual/en/function.session-write-close.php
     */
    public function close()
    {
        $this->intercept->call('session_write_close');
    }

    /**
     * Initialize session data & Start Session.
     * returns true if a session was successfully started
     *
     * @see session_start()
     * @link http://php.net/manual/en/function.session-start.php
     *
     * @return bool
     */
    public function start()
    {
        if (($result = $this->intercept->call('session_start'))) {
            $this->moveFlash();
        }

        return $result;
    }

    /**
     * Set Session Name.
     *
     * The session name references the name of the session, which is
     * used in cookies and URLs (e.g. PHPSESSID). It
     * should contain only alphanumeric characters; it should be short and
     * descriptive (i.e. for users with enabled cookie warnings).
     *
     * @see session_name()
     * @link http://php.net/manual/en/function.session-name.php
     *
     * @param string $name the session name to be set
     * @return string the session name set
     */
    public function setName($name)
    {
        if (! $this->intercept->call('is_string', $name)) {
            throw new InvalidArgumentException(
                sprintf(
                    "Session name must be as a string %s given.",
                    gettype($name)
                )
            );
        }

        return $this->intercept->call('session_name', $name);
    }

    /**
     * Get Session Name
     *
     * @return string
     */
    public function getName()
    {
        return $this->intercept->call('session_name');
    }

    /**
     *
     * Sets the session save path.
     *
     * @see session_save_path()
     * @link http://php.net/manual/en/function.session-save-path.php
     *
     * @param string $path The new save path.
     * @return string
     */
    public function setSavePath($path)
    {
        if ($this->intercept->call('is_string', $path)) {
            throw new InvalidArgumentException(
                "Session save path must be as a string %s given.",
                gettype($path)
            );
        }

        return $this->intercept->call('session_save_path', $path);
    }

    /**
     * Get the session save path.
     *
     * @see session_save_path()
     * @link http://php.net/manual/en/function.session-save-path.php
     *
     * @return string
     */
    public function getSavePath()
    {
        return $this->intercept->call('session_save_path');
    }

    /**
     * Resume the session if session has not been started
     *
     * @return bool
     */
    public function resume()
    {
        if ($this->isStarted()) {
            return true;
        }

        if ($this->isResumeAble()) {
            return $this->start();
        }

        return false;
    }

    /**
     * Check if current session is resume able
     *
     * @return bool
     */
    public function isResumeAble()
    {
        $name = $this->getName();
        return isset($this->cookies[$name]);
    }

    /**
     * Sets the delete-cookie callable.
     *
     * If parameter is empty value, the session cookie will be deleted using the
     * traditional way, i.e. using an expiration date in the past.
     *
     * @param callable|null $deleteCookieInstance The callable to invoke when deleting the
     * session cookie.
     * @throws InvalidArgumentException
     */
    public function setDeleteCookieInstance($deleteCookieInstance)
    {
        if ($deleteCookieInstance && !is_callable($deleteCookieInstance)) {
            throw new \InvalidArgumentException(
                "Delete Cookie instance must be callable",
                E_USER_ERROR
            );
        }

        $this->deleteCookieInstance = $deleteCookieInstance;
        if (! $this->deleteCookieInstance) {
            $intercept = $this->intercept;
            $default_params = $this->cookieParams;
            $this->deleteCookieInstance = function (
                $name,
                array $params
            ) use (
                $intercept,
                $default_params
) {
                // merge default
                $params = array_merge($default_params, $params);
                // call delete cookie
                $intercept->call(
                    'setCookie',
                    $name,
                    '',
                    time() - 42000,
                    $params[Session::PARAM_PATH],
                    $params[Session::PARAM_DOMAIN]
                );
            };
        }
    }

    /**
     * Destroys the session entirely.
     *
     * @see session_destroy()
     * @link http://php.net/manual/en/function.session-destroy.php
     *
     * @return bool
     */
    public function destroy()
    {
        if (! $this->isStarted()) {
            $this->start();
        }

        $name = $this->getName();
        $params = $this->getCookieParams();
        $this->clear();

        $destroyed = $this->intercept->call('session_destroy');
        if ($destroyed) {
            call_user_func($this->deleteCookieInstance, $name, $params);
        }

        return $destroyed;
    }

    /**
     *
     * Set the session cookie parameters.
     * Param array keys are:
     *
     * lifetime : Lifetime of the session cookie, defined in seconds.
     * path     : Path on the domain where the cookie will work.
     *            Use a single slash ('/') for all paths on the domain.
     * domain   : Cookie domain, for example 'www.php.net'.
     *            To make cookies visible on all sub domains then the domain must be
     *            prefixed with a dot like '.php.net'.
     * secure   : If TRUE cookie will only be sent over secure connections.
     * httponly : If set to TRUE then PHP will attempt to send the httponly
     *            flag when setting the session cookie.
     *
     * @param array $params The array of session cookie param keys and values.
     *
     * @see session_set_cookie_params()
     * @link http://php.net/manual/en/function.session-set-cookie-params.php
     */
    public function setCookieParams(array $params)
    {
        $this->cookieParams = array_merge($this->cookieParams, $params);
        $this->intercept->call(
            'session_set_cookie_params',
            $this->cookieParams[Session::PARAM_LIFETIME],
            $this->cookieParams[Session::PARAM_PATH],
            $this->cookieParams[Session::PARAM_DOMAIN],
            $this->cookieParams[Session::PARAM_SECURE],
            $this->cookieParams[Session::PARAM_HTTP_ONLY]
        );
    }

    /**
     *
     * Gets the session cookie params.
     *
     * @return array
     */
    public function getCookieParams()
    {
        return $this->cookieParams;
    }

    /**
     * Sets the session cache expire time.
     *
     * @see session_cache_expire()
     * @link http://php.net/manual/en/function.session-cache-expire.php
     *
     * @param int $expire The expiration time in seconds.
     * @return int
     */
    public function setCacheExpire($expire)
    {
        return $this->intercept->call('session_cache_expire', $expire);
    }

    /**
     *
     * Gets the session cache expire time.
     *
     * @see session_cache_expire()
     * @link http://php.net/manual/en/function.session-cache-expire.php
     *
     * @return int The cache expiration time in seconds.
     */
    public function getCacheExpire()
    {
        return $this->intercept->call('session_cache_expire');
    }

    /**
     *
     * Sets the session cache limiter value.
     *
     * @see session_cache_limiter()
     * @link http://php.net/manual/en/function.session-cache-limiter.php
     *
     * @param string $cache_limiter If cache_limiter is specified, the name of the
     *                        current cache limiter is changed to the new value.
     * @return string
     */
    public function setCacheLimiter($cache_limiter)
    {
        return $this->intercept->call('session_cache_limiter', $cache_limiter);
    }

    /**
     * Get the session cache limiter value.
     *
     * @see session_cache_limiter()
     * @link http://php.net/manual/en/function.session-cache-limiter.php
     *
     * @return string The limiter value.
     */
    public function getCacheLimiter()
    {
        return $this->intercept->call('session_cache_limiter');
    }

    /**
     * Gets a new session segment instance by name. Segments with the same
     * name will be different objects but will reference the same $_SESSION
     * values, so it is possible to have two or more objects that share state.
     * For good or bad, this a function of how $_SESSION works.
     *
     * @param string $name The name of the session segment, typically a
     * fully-qualified class name.
     *
     * @return Segment New Segment instance.
     *
     */
    public function getSegment($name)
    {
        $segment = new Segment($this, $name);
        return $segment;
    }
}
