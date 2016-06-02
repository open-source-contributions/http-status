<?php
namespace Narrowspark\HttpStatus\Exception;

class PreconditionFailedException extends AbstractClientErrorException
{
    /**
     * @var string
     */
    protected $message = '412 Precondition Failed';

    /**
     * @var int
     */
    protected $code = 412;
}