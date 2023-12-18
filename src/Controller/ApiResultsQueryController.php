<?php

namespace App\Controller;

use App\Entity\Result;
use App\Entity\User;
use App\Utility\Utils;
use Doctrine\ORM\EntityManagerInterface;
use JsonException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use function in_array;

/**
 * Class ApiResultsController
 *
 * @package App\Controller
 *
 * @Route(
 *     path=ApiResultsQueryInterface::RUTA_API,
 *     name="api_results_"
 * )
 */
class ApiResultsQueryController extends AbstractController implements ApiResultsQueryInterface
{
    private const HEADER_CACHE_CONTROL = 'Cache-Control';
    private const HEADER_ETAG = 'ETag';
    private const HEADER_ALLOW = 'Allow';

    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * @see ApiResultsQueryInterface::cgetAction()
     *
     * @Route(
     *     path=".{_format}/{sort?id}",
     *     defaults={ "_format": "json", "sort": "id" },
     *     requirements={
     *         "sort": "id|email|roles",
     *         "_format": "json|xml"
     *     },
     *     methods={ Request::METHOD_GET },
     *     name="cget"
     * )
     *
     * @throws JsonException
     */
    public function cgetAction(Request $request): Response
    {
        $format = Utils::getFormat($request);
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return Utils::errorMessage( // 401
                Response::HTTP_UNAUTHORIZED,
                '`Unauthorized`: Invalid credentials.',
                $format
            );
        }

        // Obtener el usuario actual
        $currentUser = $this->getUser();

        if ($this->isGranted('ROLE_ADMIN')) {
            // Si es administrador, obtener todos los resultados
            $results = $this->entityManager
                ->getRepository(Result::class)
                ->findAll();
        } else {
            $user = $this->entityManager->getRepository(User::class)->findBy([User::EMAIL_ATTR => $currentUser->getUserIdentifier()]);
            $results = $this->entityManager->getRepository(Result::class)->findBy([Result::USER_ATTR => $user]);
        }

        // No hay resultados?
        // @codeCoverageIgnoreStart
        if (empty($results)) {
            return Utils::errorMessage(Response::HTTP_NOT_FOUND, null, $format);    // 404
        }
        // @codeCoverageIgnoreEnd

        // Caching with ETag
        $etag = md5((string) json_encode($results, JSON_THROW_ON_ERROR));
        if (($etags = $request->getETags()) && (in_array($etag, $etags) || in_array('*', $etags))) {
            return new Response(null, Response::HTTP_NOT_MODIFIED); // 304
        }

        return Utils::apiResponse(
            Response::HTTP_OK,
            ['results' => array_map(fn ($u) =>  $u, $results)],
            $format,
            [
                self::HEADER_CACHE_CONTROL => 'private',
                self::HEADER_ETAG => $etag,
            ]
        );
    }

    /**
     * @see ApiResultsQueryInterface::getAction()
     *
     * @Route(
     *     path="/{resultId}.{_format}",
     *     defaults={ "_format": null },
     *     requirements={
     *          "resultId": "\d+",
     *          "_format": "json|xml"
     *     },
     *     methods={ Request::METHOD_GET },
     *     name="get"
     * )
     *
     * @throws JsonException
     */
    public function getAction(Request $request, int $resultId): Response
    {
        $format = Utils::getFormat($request);
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return Utils::errorMessage( // 401
                Response::HTTP_UNAUTHORIZED,
                '`Unauthorized`: Invalid credentials.',
                $format
            );
        }

        // Obtener el usuario actual
        $currentUser = $this->getUser();

        /** @var Result $result */
        $result = $this->entityManager
            ->getRepository(Result::class)
            ->find($resultId);


        // Verificar que el resultado existe
        if (!$result instanceof Result) {
            return Utils::errorMessage(Response::HTTP_NOT_FOUND, null, $format);
        }

        // Verificar que el usuario actual es el propietario del resultado o es un administrador
        if (!$this->isGranted('ROLE_ADMIN') && $result->getUser()->getEmail() !== $currentUser->getUserIdentifier()) {
            return Utils::errorMessage(Response::HTTP_FORBIDDEN, 'Access Denied: You can only operate on your own results.', $format);
        }

        // Caching with ETag (password included)
        $etag = md5(json_encode($result, JSON_THROW_ON_ERROR));
        if (($etags = $request->getETags()) && (in_array($etag, $etags) || in_array('*', $etags))) {
            return new Response(null, Response::HTTP_NOT_MODIFIED); // 304
        }

        return Utils::apiResponse(
            Response::HTTP_OK,
            $result,
            $format,
            [
                self::HEADER_CACHE_CONTROL => 'private',
                self::HEADER_ETAG => $etag,
            ]
        );
    }

    /**
     * @see ApiResultsQueryInterface::optionsAction()
     *
     * @Route(
     *     path="/{resultId}.{_format}",
     *     defaults={ "resultId" = 0, "_format": "json" },
     *     requirements={
     *          "resultId": "\d+",
     *         "_format": "json|xml"
     *     },
     *     methods={ Request::METHOD_OPTIONS },
     *     name="options"
     * )
     */
    public function optionsAction(int|null $resultId): Response
    {
        $methods = $resultId !== 0
            ? [Request::METHOD_GET, Request::METHOD_PUT, Request::METHOD_DELETE]
            : [Request::METHOD_GET, Request::METHOD_POST];
        $methods[] = Request::METHOD_OPTIONS;

        return new Response(
            null,
            Response::HTTP_NO_CONTENT,
            [
                self::HEADER_ALLOW => implode(',', $methods),
                self::HEADER_CACHE_CONTROL => 'public, inmutable'
            ]
        );
    }
}
