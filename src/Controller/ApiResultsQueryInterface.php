<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Interface ApiResultsQueryInterface
 *
 * @package App\Controller
 *
 */
interface ApiResultsQueryInterface
{
    public final const RUTA_API = '/api/v1/results';

    /**
     * **CGET** Action<br>
     * Summary: Retrieves the collection of Result resources.<br>
     * _Notes_: Returns all results from the system that the user has access to.
     */
    public function cgetAction(Request $request): Response;
}
