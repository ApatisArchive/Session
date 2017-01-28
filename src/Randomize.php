<?php
namespace Apatis\Session;

use Apatis\CoreIntercept\CallableFunction;
use Apatis\Exceptions\InvalidArgumentException;

/**
 * Class Randomize
 * @package Apatis\Session
 */
class Randomize implements RandomizeInterface
{
    /**
     * @var CallableFunction
     */
    protected $intercept;

    /**
     * @var int
     */
    protected $bytes = 32;

    /**
     * Random String Characters, using native code to generate random values
     *
     * @param int $bytes
     * @return string
     */
    public function randomByte($bytes = 32)
    {
        if (!is_numeric($bytes)) {
            throw new InvalidArgumentException(
                "Bytes must be as a numeric.",
                E_USER_ERROR
            );
        }

        // fallback default to 32 bytes
        $bytes = $bytes > 0 ? $bytes : 32;
        $char = '';
        for ($i = 0; $i < $bytes; $i++) {
            $lengthCount = rand(1, 4);
            $int = '';
            while ($lengthCount > 0) {
                $int .= rand(0, 9);
                $lengthCount--;
            }
            $char .= chr($int);
        }

        return $char;
    }

    /**
     * {@inheritdoc}
     */
    public function generate()
    {
        return $this->randomByte($this->bytes);
    }
}
