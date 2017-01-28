<?php
namespace Apatis\Session;

/**
 * Interface SegmentInterface
 * @package Apatis\Session
 */
interface SegmentInterface extends \ArrayAccess
{
    /**
     * Getting Key Segment
     *
     * @param string $name      the segment key name
     * @param mixed  $default   if the segment is not exists return default
     * @return mixed
     */
    public function get($name, $default = null);

    /**
     * Set The Segment Value
     *
     * @param string $name  the segment key nae
     * @param mixed  $value the segment value
     * @return void
     */
    public function set($name, $value);

    /**
     * Remove The segment
     *
     * @param string $name the segment key name
     * @return void
     */
    public function remove($name);

    /**
     * Clear The Segment
     *
     * @return void
     */
    public function clear();

    /**
     * Set The Flash Next value for next request
     * @uses Session::FLASH_NEXT
     *
     * @param string $name
     * @param mixed  $value
     * @return void
     */
    public function flash($name, $value);

    /**
     * Set The Flash Next value for next request and current One
     * @uses Session::FLASH_NEXT
     * @uses Session::FLASH_CURRENT
     *
     * @param string $name
     * @param mixed  $value
     * @return void
     */
    public function flashBoth($name, $value);

    /**
     * Get The Current Flash Segment Session
     * @uses Session::FLASH_CURRENT as Key Name
     *
     * @param string $name      the segment key name
     * @param mixed  $default   if the segment is not exists return default
     * @return mixed
     */
    public function flashGet($name, $default = null);

    /**
     * Get The Previous Flash Segment Session
     * @uses Session::FLASH_PREV as Key Name
     *
     * @param string $name      the segment key name
     * @param mixed  $default   if the segment is not exists return default
     * @return mixed
     */
    public function flashPreviousGet($name, $default = null);

    /**
     * Get The Next Flash Segment Session
     * @uses Session::FLASH_NEXT as Key Name, the value is that current session set
     *
     * @param string $name      the segment key name
     * @param mixed  $default   if the segment is not exists return default
     * @return mixed
     */
    public function flashNextGet($name, $default = null);

    /**
     * Clear The Next Flash Session Segment Only
     * @uses Session::FLASH_NEXT as Key Name
     *
     * @return void
     */
    public function flashClear();

    /**
     * Clear The Previous Flash Session Segment Only
     * @uses Session::FLASH_PREV as Key Name
     *
     * @return void
     */
    public function flashClearPrevious();

    /**
     * Clear The Current Flash Session Segment Only
     * @uses Session::FLASH_CURRENT as Key Name
     *
     * @return void
     */
    public function flashClearCurrent();

    /**
     * Clear The Current & Next Flash Session Segment Only
     * @uses Session::FLASH_CURRENT as Key Name
     * @uses Session::FLASH_NEXT    as Key Name
     *
     * @return void
     */
    public function flashClearBoth();

    /**
     * Retains all the current flash values for the next request; values that
     * already exist for the next request take precedence.
     *
     * @return void
     */
    public function flashKeep();
}
