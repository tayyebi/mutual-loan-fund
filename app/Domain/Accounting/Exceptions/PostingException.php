<?php

namespace App\Domain\Accounting\Exceptions;

use App\Support\DomainRefusal;
use RuntimeException;

/**
 * A journal entry was rejected before it could be written — a foreign account,
 * a missing required cost center, a non-positive amount, and so on.
 */
class PostingException extends RuntimeException implements DomainRefusal {}
