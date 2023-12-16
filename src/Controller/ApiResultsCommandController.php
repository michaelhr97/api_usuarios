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

use function in_array;

/**
 * Class ApiUsersController
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

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
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
}
