<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;

/**
 * Controllers receive the request, validate, authorise, call a domain service
 * and redirect. Business rules and journal entries live in the domain layer,
 * never here.
 */
abstract class Controller
{
    use AuthorizesRequests;
    use ValidatesRequests;
}
