<?php
namespace Apatis\Session;

/**
 * Interface RandomizeInterface
 * @package Apatis\Session
 */
interface RandomizeInterface
{
    /**
     * Returning Random Token Value
     *
     * @return string
     */
    public function generate();
}
