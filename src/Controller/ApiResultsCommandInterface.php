<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ApiResultsController
 *
 * @package App\Controller
 *
 */
interface ApiResultsCommandInterface
{
    /**
     * **POST** action<br>
     * Summary: Creates a Result resource.
     *
     * @param Request $request request
     */
    public function postAction(Request $request): Response;
}
