<?php

namespace App\Exceptions;

use RuntimeException;

/** Fixes: original silently skipped missing products (fetch() returns false, multiplied by qty = 0) — now throws before any write. */
class InvalidOrderException extends RuntimeException {}
