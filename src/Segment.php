<?php
namespace Apatis\Session;

use Apatis\Exceptions\InvalidArgumentException;
use Apatis\Session\Flash\Current;
use Apatis\Session\Flash\Next;
use Apatis\Session\Flash\Prev;

/**
 * Class Segment
 * @package Apatis\Session
 */
class Segment implements SegmentInterface
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var Session
     */
    protected $session;

    /**
     * Segment constructor.
     *
     * @param Session $session Session Object
     * @param string  $name    The segment name
     */
    public function __construct(Session $session, $name)
    {
        if (!is_string($name)) {
            throw new InvalidArgumentException(
                sprintf(
                    "Segment name must be as a string %s given.",
                    gettype($name)
                )
            );
        }

        $this->session = $session;
        $this->name    = $name;
    }

    /**
     * Loads the segment only if the session has already been started, or if
     * a session is available (in which case it resumes the session first).
     *
     * @return bool
     */
    protected function resumeSession()
    {
        if ($this->session->isStarted() || $this->session->resume()) {
            $this->load();
            return true;
        }

        return false;
    }

    /**
     * Resumes a previous session, or starts a new one, and loads the segment.
     *
     * @return void
     */
    protected function resumeOrStartSession()
    {
        if (! $this->resumeSession()) {
            $this->session->start();
            $this->load();
        }
    }

    /**
     * Load Data Set Session
     */
    protected function load()
    {
        if (! isset($_SESSION[$this->name])) {
            $_SESSION[$this->name] = [];
        }

        $session = $this->session;
        if (! isset($_SESSION[$session::FLASH_PREV][$this->name])
            || ! $_SESSION[$session::FLASH_PREV][$this->name] instanceof Prev
        ) {
            $_SESSION[$session::FLASH_PREV][$this->name] = new Prev();
        }

        if (! isset($_SESSION[$session::FLASH_CURRENT][$this->name])
            || ! $_SESSION[$session::FLASH_PREV][$this->name] instanceof Current
        ) {
            $_SESSION[$session::FLASH_CURRENT][$this->name] = new Current();
        }

        if (! isset($_SESSION[$session::FLASH_NEXT][$this->name])
            || ! $_SESSION[$session::FLASH_NEXT][$this->name] instanceof Next
        ) {
            $_SESSION[$session::FLASH_NEXT][$this->name] = new Next();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function get($name, $default = null)
    {
        $this->resumeSession();
        return isset($_SESSION[$this->name][$name])
            ? $_SESSION[$this->name][$name]
            : $default;
    }

    /**
     * {@inheritdoc}
     */
    public function set($name, $value)
    {
        $this->resumeOrStartSession();
        $_SESSION[$this->name][$name] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function remove($name)
    {
        if ($this->resumeSession()
            && isset($_SESSION[$this->name])
            && is_array($_SESSION[$this->name])
        ) {
            unset($_SESSION[$this->name][$name]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        if ($this->resumeSession()) {
            $_SESSION[$this->name] = [];
        }
    }

    /**
     * Verify If The flash has valid
     */
    protected function verifyFlashSession()
    {
        $this->resumeOrStartSession();
        $session = $this->session;
        if (empty($_SESSION[$session::FLASH_NEXT][$this->name])
            || !$_SESSION[$session::FLASH_NEXT][$this->name] instanceof Next
        ) {
            $this->load();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function flash($name, $value)
    {
        $this->verifyFlashSession();
        $session = $this->session;
        /** @noinspection PhpUndefinedMethodInspection */
        $_SESSION[$session::FLASH_NEXT][$this->name]->replace($name, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function flashGet($name, $default = null)
    {
        $this->verifyFlashSession();
        $session = $this->session;
        /** @noinspection PhpUndefinedMethodInspection */
        return $_SESSION[$session::FLASH_CURRENT][$this->name]->get($name, $default);
    }

    /**
     * {@inheritdoc}
     */
    public function flashNextGet($name, $default = null)
    {
        $this->verifyFlashSession();
        $session = $this->session;
        /** @noinspection PhpUndefinedMethodInspection */
        return $_SESSION[$session::FLASH_NEXT][$this->name]->get($name, $default);
    }

    /**
     * {@inheritdoc}
     */
    public function flashClear()
    {
        $this->verifyFlashSession();
        $session = $this->session;
        $_SESSION[$session::FLASH_NEXT][$this->name] = new Next();
    }

    /**
     * {@inheritdoc}
     */
    public function flashClearCurrent()
    {
        $this->verifyFlashSession();
        $session = $this->session;
        $_SESSION[$session::FLASH_CURRENT][$this->name] = new Current();
    }

    /**
     * {@inheritdoc}
     */
    public function flashPreviousGet($name, $default = null)
    {
        $this->verifyFlashSession();
        $session = $this->session;
        /** @noinspection PhpUndefinedMethodInspection */
        return $_SESSION[$session::FLASH_PREV][$this->name]->get($name, $default);
    }

    /**
     * {@inheritdoc}
     */
    public function flashClearPrevious()
    {
        $this->verifyFlashSession();
        $session = $this->session;
        $_SESSION[$session::FLASH_PREV][$this->name] = new Prev();
    }

    /**
     * {@inheritdoc}
     */
    public function flashBoth($name, $value)
    {
        $this->verifyFlashSession();
        $session = $this->session;
        /** @noinspection PhpUndefinedMethodInspection */
        $_SESSION[$session::FLASH_NEXT][$this->name]->replace($name, $value);
        /** @noinspection PhpUndefinedMethodInspection */
        $_SESSION[$session::FLASH_CURRENT][$this->name]->replace($name, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function flashClearBoth()
    {
        $this->flashClear();
        $this->flashClearCurrent();
    }

    /**
     * {@inheritdoc}
     */
    public function flashKeep()
    {
        $this->verifyFlashSession();
        $session = $this->session;
        /** @noinspection PhpUndefinedMethodInspection */
        $_SESSION[$session::FLASH_NEXT][$this->name]->replace(
            $_SESSION[$session::FLASH_CURRENT][$this->name]->all()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        if ($this->resumeSession()) {
            return ! empty($_SESSION[$this->name]) && isset($_SESSION[$this->name][$offset]);
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        $this->set($offset, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        $this->remove($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        $this->get($offset);
    }
}
