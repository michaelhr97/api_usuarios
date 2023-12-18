<?php

namespace App\Tests\Controller;

use App\Entity\Message;
use App\Entity\Result;
use App\Entity\User;
use Faker\Factory as FakerFactoryAlias;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ApiResultsControllerTest
 *
 * @package App\Tests\Controller
 * @group   controllers
 *
 * @coversDefaultClass \App\Controller\ApiResultsQueryController
 */
class ApiResultsControllerTest extends BaseTestCase
{
    private const RUTA_API_USERS = '/api/v1/users';
    private const RUTA_API_RESULTS = '/api/v1/results';

    /** @var array<string,string> $adminHeaders */
    private static array $adminHeaders;

    /**
     * Test OPTIONS /results[/resultId] 204 No Content
     *
     * @covers ::__construct
     * @covers ::optionsAction
     * @return void
     */
    public function testOptionsUserAction204NoContent(): void
    {
        // OPTIONS /api/v1/users
        self::$client->request(
            Request::METHOD_OPTIONS,
            self::RUTA_API_RESULTS
        );
        $response = self::$client->getResponse();

        self::assertSame(
            Response::HTTP_NO_CONTENT,
            $response->getStatusCode()
        );
        self::assertNotEmpty($response->headers->get('Allow'));

        // OPTIONS /api/v1/results/{id}
        self::$client->request(
            Request::METHOD_OPTIONS,
            self::RUTA_API_RESULTS . '/' . self::$faker->numberBetween(1, 100)
        );

        self::assertSame(
            Response::HTTP_NO_CONTENT,
            $response->getStatusCode()
        );
        self::assertNotEmpty($response->headers->get('Allow'));
    }

}
