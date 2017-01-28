<?php
namespace Apatis\Session;

/**
 * Class TokenFactory
 * @package Apatis\Session
 */
class TokenFactory
{
    const SEGMENT_NAME = Token::class;

    /**
     * @var Randomize
     */
    protected $random;

    /**
     * TokenFactory constructor.
     * @param RandomizeInterface|null $randomize
     */
    public function __construct(RandomizeInterface $randomize = null)
    {
        $this->random = $randomize?: new Randomize();
    }

    /**
     * Get Token Object
     *
     * @param Session $session
     * @return Token
     */
    public function instance(Session $session)
    {
        $segment = $session->getSegment(static::SEGMENT_NAME);
        return new Token($segment, $this->random);
    }
}
