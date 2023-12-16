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

    /**
     * **DELETE** Action<br>
     * Summary: Removes the Result resource.<br>
     * _Notes_: Deletes the result identified by &#x60;resultId&#x60;.
     *
     * @param int $resultId Result id
     */
    public function deleteAction(Request $request, int $resultId): Response;
}
