<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Result;
use App\Utility\Utils;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use JsonException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

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
class ApiResultsCommandController extends AbstractController implements ApiResultsCommandInterface
{

    private AuthorizationCheckerInterface $authorizationChecker;


    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        $this->authorizationChecker = $authorizationChecker;
    }
    /**
     * @see ApiResultsCommandInterface::postAction()
     *
     * @Route(
     *     path=".{_format}",
     *     defaults={ "_format": null },
     *     requirements={
     *         "_format": "json|xml"
     *     },
     *     methods={ Request::METHOD_POST },
     *     name="post"
     * )
     * @throws JsonException
     */
    public function postAction(Request $request): Response
    {
        $format = Utils::getFormat($request);
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return Utils::errorMessage( // 401
                Response::HTTP_UNAUTHORIZED,
                '`Unauthorized`: Invalid credentials.',
                $format
            );
        }



        $body = $request->getContent();
        $postData = json_decode((string) $body, true, 512, JSON_THROW_ON_ERROR);

        if (!isset($postData[Result::RESULT_ATTR], $postData[User::EMAIL_ATTR])) {
            // 422 - Unprocessable Entity -> Faltan datos
            return Utils::errorMessage(Response::HTTP_UNPROCESSABLE_ENTITY, null, $format);
        }

        // hay datos -> procesarlos
        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy([User::EMAIL_ATTR => $postData[User::EMAIL_ATTR]]);

        if (!$user instanceof User) {    // 400 - Bad Request
            return Utils::errorMessage(Response::HTTP_NOT_FOUND, null, $format);
        }

        if (!$this->authorizationChecker->isGranted('ROLE_ADMIN')) {
            // Restricciones adicionales para usuarios normales (ROLE_USER)
            $currentUser = $this->getUser();
            $postData = json_decode((string) $request->getContent(), true, 512, JSON_THROW_ON_ERROR);

            // Verificar que el usuario actual solo está operando con sus propios resultados
            if ($currentUser instanceof User && $currentUser->getEmail() !== $postData[User::EMAIL_ATTR]) {
                return Utils::errorMessage(Response::HTTP_FORBIDDEN, 'Access Denied: You can only operate on your own results.', $format);
            }
        }

        // 201 - Created
        $result = new Result(
            strval($postData[Result::RESULT_ATTR]),
            $user,
            new DateTime('now')
        );


        $this->entityManager->persist($result);
        $this->entityManager->flush();

        return Utils::apiResponse(
            Response::HTTP_CREATED,
            $result,
            $format,
        );
    }

    /**
     * @see ApiResultsCommandInterface::putAction()
     *
     * @Route(
     *     path="/{resultId}.{_format}",
     *     defaults={ "_format": null },
     *     requirements={
     *          "resultId": "\d+",
     *         "_format": "json|xml"
     *     },
     *     methods={ Request::METHOD_PUT },
     *     name="put"
     * )
     * @throws JsonException
     */
    public function putAction(Request $request, int $resultId): Response
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

        $body = $request->getContent();
        $postData = json_decode($body, true);

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

        $result->setResult($postData[Result::RESULT_ATTR]);
        $result->setTime(new DateTime('now'));
        $this->entityManager->flush();

        return Utils::apiResponse(
            209,                        // 209 - Content Returned
            $result,
            $format
        );
    }

    /**
     * @see ApiResultsCommandInterface::deleteAction()
     *
     * @Route(
     *     path="/{resultId}.{_format}",
     *     defaults={ "_format": null },
     *     requirements={
     *          "resultId": "\d+",
     *         "_format": "json|xml"
     *     },
     *     methods={ Request::METHOD_DELETE },
     *     name="delete"
     * )
     */
    public function deleteAction(Request $request, int $resultId): Response
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

        $this->entityManager->remove($result);
        $this->entityManager->flush();

        return Utils::apiResponse(Response::HTTP_NO_CONTENT);
    }
}
