<?php

declare(strict_types=1);

/**
 * This file is part of Narrowspark Framework.
 *
 * (c) Daniel Bannert <d.bannert@anolilab.de>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Narrowspark\HttpStatus\Exception;

class FailedDependencyException extends AbstractClientErrorException
{
    /** @var string */
    protected $message = '424 Failed Dependency';

    /** @var int */
    protected $statusCode = 424;
}
