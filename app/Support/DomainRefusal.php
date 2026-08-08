<?php

namespace App\Support;

/**
 * Marks an exception that represents a refusal the operator should read, rather
 * than a fault: a missing exchange rate, no active policy, a closed period, an
 * entry that would not balance.
 *
 * Anything implementing this is safe to show verbatim — the messages are written
 * for humans and contain no internals.
 */
interface DomainRefusal {}
