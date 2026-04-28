<?php

namespace App\Exceptions;

use RuntimeException;

/** New: original had no customer deduplication — thrown by CreateCustomerHandler before any INSERT is attempted. */
class DuplicateEmailException extends RuntimeException {}
