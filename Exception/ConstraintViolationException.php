<?php

/*
 * This file is part of the Sonatra package.
 *
 * (c) François Pluchino <francois.pluchino@sonatra.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonatra\Component\Resource\Exception;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Base ConstraintViolationException for external constraint violations.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class ConstraintViolationException extends RuntimeException
{
    /**
     * @var ConstraintViolationListInterface
     */
    protected $violations;

    /**
     * @param ConstraintViolationListInterface $violations The constraint violations
     * @param string                           $message    The message of exception
     * @param int                              $code       The code of exception
     * @param \Exception                       $previous   The previous exception
     */
    public function __construct(ConstraintViolationListInterface $violations,
                                $message = null, $code = 0, \Exception $previous = null)
    {
        parent::__construct(null !== $message ? $message : Response::$statusTexts[422], $code, $previous);

        $this->violations = $violations;
    }

    /**
     * Get the constraint violations.
     *
     * @return ConstraintViolationListInterface
     */
    public function getConstraintViolations()
    {
        return $this->violations;
    }
}
