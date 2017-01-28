<?php
namespace Apatis\Session;

/**
 * Class SegmentFactory
 * @package Apatis\Session
 */
class SegmentFactory
{
    /**
     * Get New Instance of Segment
     *
     * @param Session $session The Session Object
     * @param string  $name    The Segment Name
     * @return Segment
     */
    public function instance(Session $session, $name)
    {
        return new Segment($session, $name);
    }
}
