<?php

namespace App\Tests\Controller;

use App\Entity\Result;
use App\Entity\User;
use Faker\Factory as FakerFactoryAlias;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Generator;
use JetBrains\PhpStorm\ArrayShape;

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


    /**
     * Test POST /users 201 Created
     *
     * @return array<string,string> user data
     */
    public function testPostResultAction201Created(): array
    {
        $user = [
            User::EMAIL_ATTR => self::$faker->email(),
            User::PASSWD_ATTR => self::$faker->password(),
            User::ROLES_ATTR => [self::$faker->word()],
        ];
        self::$adminHeaders = $this->getTokenHeaders(
            self::$role_admin[User::EMAIL_ATTR],
            self::$role_admin[User::PASSWD_ATTR]
        );

        // 201
        self::$client->request(
            Request::METHOD_POST,
            self::RUTA_API_USERS,
            [],
            [],
            self::$adminHeaders,
            strval(json_encode($user))
        );
        $response = self::$client->getResponse();

        $p_data = [
            Result::RESULT_ATTR => self::$faker->randomDigitNotNull,
            User::EMAIL_ATTR => $user['email'],
            Result::TIME_ATTR => null
        ];

        self::$client->request(
            Request::METHOD_POST,
            self::RUTA_API_RESULTS,
            [],
            [],
            self::$adminHeaders,
            json_encode($p_data)
        );

        $response = self::$client->getResponse();
        self::assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        self::assertTrue($response->isSuccessful());
        self::assertJson(strval($response->getContent()));
        $result = json_decode(strval($response->getContent()), true);
        self::assertNotEmpty($result['id']);
        self::assertSame($p_data[User::EMAIL_ATTR], $user[User::EMAIL_ATTR]);
        self::assertSame($p_data[Result::RESULT_ATTR], $result[Result::RESULT_ATTR]);
        return $result;
    }


    /**
     * Test GET /results 200 Ok
     *
     * @depends testPostResultAction201Created
     *
     * @return string ETag header
     */
    public function testCGetResultAction200Ok(): string
    {
        self::$client->request(Request::METHOD_GET, self::RUTA_API_RESULTS, [], [], self::$adminHeaders);
        $response = self::$client->getResponse();
        self::assertTrue($response->isSuccessful());
        self::assertNotNull($response->getEtag());
        $r_body = strval($response->getContent());
        self::assertJson($r_body);
        $results = json_decode($r_body, true);
        self::assertArrayHasKey('results', $results);

        return (string) $response->getEtag();
    }

    /**
     * Test GET /results 200 Ok (with XML header)
     *
     * @param   array<string,string> $result result returned by testPostResultAction201Created()
     * @return  void
     * @depends testPostResultAction201Created
     */
    public function testCGetResultAction200XmlOk(array $result): void
    {
        self::$client->request(
            Request::METHOD_GET,
            self::RUTA_API_RESULTS . '/' . $result['id'],
            [],
            [],
            array_merge(
                self::$adminHeaders,
                ['HTTP_ACCEPT' => 'application/xml']
            )
        );
        $response = self::$client->getResponse();
        self::assertTrue($response->isSuccessful(), strval($response->getContent()));
        self::assertNotNull($response->getEtag());
        self::assertTrue($response->headers->contains('content-type', 'application/xml'));
    }

    /**
     * Test GET /results/{resultId} 200 Ok
     *
     * @param   array<string,string> $result result returned by testPostResultAction201Created()
     * @return  string ETag header
     * @depends testPostResultAction201Created
     */
    public function testGetResultAction200Ok(array $result): string
    {
        self::$client->request(
            Request::METHOD_GET,
            self::RUTA_API_RESULTS . '/' . $result['id'],
            [],
            [],
            self::$adminHeaders
        );
        $response = self::$client->getResponse();

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertNotNull($response->getEtag());
        $r_body = (string) $response->getContent();
        self::assertJson($r_body);
        $result_aux = json_decode($r_body, true);
        self::assertSame($result['id'], $result_aux['id']);

        return (string) $response->getEtag();
    }

    /**
     * Test PUT /results/{resultId} 209 Content Returned
     *
     * @param   array<string,string> $result result returned by testPostResultAction201Created()
     * @return  array<string,string> modified result data
     * @depends testPostResultAction201Created
     */
    public function testPutResultAction209ContentReturned(array $result): array
    {
        $updatedResult = self::$faker->randomDigitNotNull;
        $p_data = [
            Result::RESULT_ATTR => $updatedResult
        ];

        self::$client->request(
            Request::METHOD_PUT,
            self::RUTA_API_RESULTS . '/' . $result['id'],
            [],
            [],
            self::$adminHeaders,
            strval(json_encode($p_data))
        );
        $response = self::$client->getResponse();

        self::assertSame(209, $response->getStatusCode());
        $r_body = (string) $response->getContent();
        self::assertJson($r_body);
        $result_aux = json_decode($r_body, true);
        self::assertSame($result['id'], $result_aux['id']);
        self::assertSame($p_data[Result::RESULT_ATTR], $result_aux[Result::RESULT_ATTR]);

        return $result_aux;
    }

    /**
     * Test DELETE /results/{resultId} 204 No Content
     *
     * @param   array $result result returned by testPostResultAction201Created()
     * @return  int resultId
     * @depends testPostResultAction201Created
     * @depends testGetResultAction200Ok
     * @depends testCGetResultAction200Ok
     */
    public function testDeleteResultAction204NoContent(array $result): int
    {
        self::$client->request(
            Request::METHOD_DELETE,
            self::RUTA_API_RESULTS . '/' . $result['id'],
            [],
            [],
            self::$adminHeaders

        );
        $response = self::$client->getResponse();
        self::assertSame(
            Response::HTTP_NO_CONTENT,
            $response->getStatusCode()
        );
        self::assertEmpty((string) $response->getContent());

        return $result['id'];
    }

    /**
     * Test GET    /results 401 UNAUTHORIZED
     * Test POST   /results 401 UNAUTHORIZED
     * Test GET    /results/{resultId} 401 UNAUTHORIZED
     * Test PUT    /results/{resultId} 401 UNAUTHORIZED
     * Test DELETE /results/{resultId} 401 UNAUTHORIZED
     *
     * @param string $method
     * @param string $uri
     * @dataProvider providerRoutes401
     * @return void
     */
    public function testResultStatus401Unauthorized(string $method, string $uri): void
    {
        self::$client->request(
            $method,
            $uri,
            [],
            [],
            ['HTTP_ACCEPT' => 'application/json']
        );
        $this->checkResponseErrorMessage(
            self::$client->getResponse(),
            Response::HTTP_UNAUTHORIZED
        );
    }

    /**
     * Test GET    /results/{resultId} 404 NOT FOUND
     * Test PUT    /results/{resultId} 404 NOT FOUND
     * Test DELETE /results/{resultId} 404 NOT FOUND
     *
     * @param string $method
     * @param int $resultId user id. returned by testDeleteResultAction204NoContent()
     * @return void
     * @dataProvider providerRoutes404
     * @depends      testDeleteResultAction204NoContent
     */
    public function testResultStatus404NotFound(string $method, int $resultId): void
    {
        self::$client->request(
            $method,
            self::RUTA_API_RESULTS . '/' . $resultId,
            [],
            [],
            self::$adminHeaders
        );
        $this->checkResponseErrorMessage(
            self::$client->getResponse(),
            Response::HTTP_NOT_FOUND
        );
    }


    /**
     * * * * * * * * * *
     * P R O V I D E R S
     * * * * * * * * * *
     */

    /**
     * User provider (incomplete) -> 422 status code
     *
     * @return Generator user data [email, password]
     */
    #[ArrayShape([
        'no_email' => "array",
        'no_passwd' => "array",
        'nothing' => "array"
    ])]
    public function userProvider422(): Generator
    {
        $faker = FakerFactoryAlias::create('es_ES');
        $email = $faker->email();
        $password = $faker->password();

        yield 'no_email' => [null, $password];
        yield 'no_passwd' => [$email, null];
        yield 'nothing' => [null, null];
    }

    /**
     * Route provider (expected status: 401 UNAUTHORIZED)
     *
     * @return Generator name => [ method, url ]
     */
    #[ArrayShape([
        'cgetAction401' => "array",
        'getAction401' => "array",
        'postAction401' => "array",
        'putAction401' => "array",
        'deleteAction401' => "array"
    ])]
    public function providerRoutes401(): Generator
    {
        yield 'cgetAction401' => [Request::METHOD_GET, self::RUTA_API_RESULTS];
        yield 'getAction401' => [Request::METHOD_GET, self::RUTA_API_RESULTS . '/1'];
        yield 'postAction401' => [Request::METHOD_POST, self::RUTA_API_RESULTS];
        yield 'putAction401' => [Request::METHOD_PUT, self::RUTA_API_RESULTS . '/1'];
        yield 'deleteAction401' => [Request::METHOD_DELETE, self::RUTA_API_RESULTS . '/1'];
    }

    /**
     * Route provider (expected status 404 NOT FOUND)
     *
     * @return Generator name => [ method ]
     */
    #[ArrayShape([
        'getAction404' => "array",
        'putAction404' => "array",
        'deleteAction404' => "array"
    ])]
    public function providerRoutes404(): Generator
    {
        yield 'getAction404' => [Request::METHOD_GET];
        yield 'putAction404' => [Request::METHOD_PUT];
        yield 'deleteAction404' => [Request::METHOD_DELETE];
    }
}
