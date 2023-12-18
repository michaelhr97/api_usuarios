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
     * Test GET /results 304 NOT MODIFIED
     *
     * @param string $etag returned by testCGetResultAction200Ok
     *
     * @depends testCGetResultAction200Ok
     */
    public function testCGetResultAction304NotModified(string $etag): void
    {
        $headers = array_merge(
            self::$adminHeaders,
            ['HTTP_If-None-Match' => [$etag]]
        );
        self::$client->request(
            Request::METHOD_GET,
            self::RUTA_API_RESULTS,
            [],
            [],
            $headers
        );
        $response = self::$client->getResponse();
        self::assertSame(Response::HTTP_NOT_MODIFIED, $response->getStatusCode());
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
}
