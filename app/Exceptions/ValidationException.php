<?php

namespace App\Exceptions;

/**
 * Thrown for business-rule/input validation failures that should surface
 * to the client as HTTP 400 instead of the default 500 server error.
 */
class ValidationException extends \Exception
{
}
