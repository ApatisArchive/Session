<?php
namespace Apatis\Session;

/**
 * Class Token
 * @package Apatis\Session
 */
class Token
{
    /**
     * @var Segment
     */
    protected $segment;

    /**
     * @var RandomizeInterface
     */
    protected $random;

    /**
     * Token constructor.
     * @param Segment            $segment
     * @param RandomizeInterface $randomize
     */
    public function __construct(Segment $segment, RandomizeInterface $randomize)
    {
        $this->segment = $segment;
        $this->random  = $randomize;
    }

    public function verify($token)
    {
    }

    /**
     *
     * Regenerates the value of the outgoing CSRF token.
     *
     * @return void
     */
    public function regenerate()
    {
        $hash = hash('sha256', $this->random->generate());
        $this->segment['value'] = $hash;
    }
}
