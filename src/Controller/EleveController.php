<?php

namespace App\Controller;

use App\Entity\Eleve;
use JMS\Serializer\Serializer;
use App\Repository\EleveRepository;
use JMS\Serializer\SerializerInterface;
use App\Repository\ProfesseurRepository;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializationContext;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Service\VersioningService;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security as secu;
use OpenApi\Annotations as OA;

class EleveController extends AbstractController
{

    //CRUD

    //GETALL
    /**
     * Cette méthode permet de récupérer l'ensemble des élèves.
     * 
     * @OA\Response(
     *      response=200,
     *      description="Retourne la liste des élèves",
     *      @OA\JsonContent(
     *          type="array",
     *          @OA\Items(ref=@Model(type=Eleve::class, groups={"getEleve"}))
     *      )
     * )
     * 
     * @OA\Parameter(
     *      name="page",
     *      in="query",
     *      description="La page que l'on veut récupérer",
     *      @OA\Schema(type="int")
     * )
     * 
     * @OA\Parameter(
     *      name="limit",
     *      in="query",
     *      description="Le nombre d'éléments que l'on veut récupérer",
     *      @OA\Schema(type="int")
     * )
     * 
     * @OA\Tag(name="Eleve")
     * 
     * @param VersioningService $versioningService
     * @param Security $security
     * @param EleveRepository $eleveRepo
     * @param SerializerInterface $serializer
     * @param Request $request
     * @param TagAwareCacheInterface $cachePool
     * @return JsonResponse
     */
    #[Route('api/eleve', name: 'app_eleve', methods:['GET'])]
    public function getAllEleve(VersioningService $versioningService,Security $security, EleveRepository $eleveRepo, 
    SerializerInterface $serializer, Request $request, TagAwareCacheInterface $cachePool): JsonResponse
    {
        //Creéation de la pagination
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 10);

        //Cache
        $idCache = "getEleve-" . $page . "-" . $limit."-".implode(',', $security->getUser()->getRoles());

        $jsonEleveList = $cachePool->get($idCache, function(ItemInterface $item) use ($eleveRepo, $page, $limit, $serializer, $versioningService) {
            $item->tag("eleveCache");
            $eleveList = $eleveRepo->findAllWithPagination($page, $limit);

            $version = $versioningService->getVersion();
            $context = SerializationContext::create()->setGroups(['getEleve']);
            $context->setVersion($version);
            return $serializer->serialize($eleveList, 'json', $context);
        });
        //Retourne la réponse en JSON
        return new JsonResponse($jsonEleveList, Response::HTTP_OK, [], true);      
    }

    //DETAIL
     /**
     * Cette méthode permet de récupérer un seul élève selon son id.
     * 
     * @OA\Response(
     *      response=200,
     *      description="Retourne l'élève demandé",
     *      @OA\JsonContent(
     *          type="array",
     *          @OA\Items(ref=@Model(type=Eleve::class, groups={"getEleve"}))
     *      )
     * )
     * @OA\Parameter(
     *      name="id",
     *      in="path",
     *      description="L'id de l'élève que l'on veut retourner",
     *      @OA\Schema(type="string")
     * )
     * 
     * @OA\Tag(name="Eleve")
     * 
     * @param VersioningService $versioningService
     * @param Eleve $eleve
     * @param SerializerInterface $serializer
     * @return JsonResponse
     */
    #[Route('api/eleve/{id}', name: 'app_detail_eleve',methods:['GET'])]
    public function getDetailEleve(VersioningService $versioningService,Eleve $eleve, SerializerInterface $serializer): JsonResponse
    {
    //Version de l'API
    $version = $versioningService->getVersion();
    $context = SerializationContext::create()->setGroups(['getEleve']);
    $context->setVersion($version);
    $jsonEleve = $serializer->serialize($eleve, 'json', $context);
    //Réponse en JSON 
    return new JsonResponse($jsonEleve, Response::HTTP_OK, [],true);
    }

    //DELETE
    /**
     * Cette méthode permet de supprimer un seul élève selon son id.
     * 
     * @OA\Response(
     *      response=204,
     *      description="Supprime l'élève demandé",
     *      @OA\JsonContent(
     *          type="array",
     *         @OA\Items(type="boolean")
     *      )
     * )
     * @OA\Parameter(
     *      name="id",
     *      in="path",
     *      description="L'id de l'élève que l'on veut supprimer",
     *      @OA\Schema(type="string")
     * )
     * 
     * @OA\Tag(name="Eleve")
     * 
     * @param Eleve $eleve
     * @param EntityManagerInterface $em
     * @param TagAwareCacheInterface $cachePool
     * @return JsonResponse
     */
    #[Route('api/eleve/{id}', name: 'app_delete_eleve',methods:['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour supprimer un élève ')]
    public function deleteEleve(Eleve $eleve, EntityManagerInterface $em, TagAwareCacheInterface $cachePool): JsonResponse{
        //cache
        $cachePool->invalidateTags(["eleveCache"]);
        //Enlève l'élève
        $em->remove($eleve);
        //Envoie dans la BDD
        $em->flush();

        //Retourne un HTTP NO CONTENT(204)
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    //CREATE
    /**
    * Cette méthode permet de créer un élève.
    *
    * @OA\Response(
    *   response=201,
    *   description="Retourne l'élève créer",
    *   @OA\JsonContent(
    *       type="array",
    *       @OA\Items(ref=@Model(type=Eleve::class,
    *       groups={"getEleve"}))
    *   )
    *  )
    *
    * @OA\Response(
    *   response=400,
    *   description="Mauvaise requête",
    *  )
    * 
    * @OA\RequestBody(
    *     required=true,
    *     @OA\JsonContent(
    *         example={
    *             "prenom": "John",
    *             "nom": "Doe",
    *             "moyenne": 13.0,
    *             "idProf": 100
    *         },
    *         @OA\Schema (
    *              type="object",
    *              @OA\Property(property="prenom", required=true, description="Prénom de l'élève", type="string"),
    *              @OA\Property(property="nom", required=true, description="Nom de l'élève", type="string"),
    *              @OA\Property(property="moyenne", required=true, description="Moyenne de l'élève", type="number"),
    *              @OA\Property(property="idProf", required=false, description="L'identifiant du professeur de l'élève", type="integer"),
    *         )
    *     )
    * )
    * @OA\Tag(name="Eleve")
    * @param VersioningService $versioningService
    * @param Request $request
    * @param SerializerInterface $serializer
    * @param EntityManagerInterface $em
    * @param UrlGeneratorInterface $urlGenerator
    * @param ProfesseurRepository $profRepo
    * @param ValidatorInterface $validator
    * @return JsonResponse
    */
    #[Route('/api/eleve', name:"app_create_eleve", methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour créer un élève ')]
    public function createEleve(VersioningService $versioningService, ValidatorInterface $validator, Request $request,
     SerializerInterface $serializer, EntityManagerInterface $em, UrlGeneratorInterface $urlGenerator, ProfesseurRepository $profRepo): JsonResponse
    {

        $eleve = $serializer->deserialize($request->getContent(), Eleve::class, 'json');

        $content = $request->toArray();
        $idProf = $content['idProf'] ?? -1;
        $eleve->setProfesseur($profRepo->find($idProf));

        //on vérifie les erreurs
        $errors = $validator->validate($eleve);
        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors,'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        // if(gettype($idProf) != "integer") {
        //     $em->persist($eleve->getProfesseur());
        // } else {
        //     $eleve->setAuthor($profRepo->find($idProf));
        // }

        //préparation de la requête 
        $em->persist($eleve);
        //Envoie la requête
        $em->flush();

        $version = $versioningService->getVersion();
        //on reprend les "groups" créés auparavant
        $context = SerializationContext::create()->setGroups(['getEleve']);
        $jsonEleve = $serializer->serialize($eleve, 'json', $context);
        $context->setVersion( $version);
        //on génère une URL avec l'id de l'élève crée
        $location = $urlGenerator->generate('app_detail_eleve', ['id' => $eleve->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        //on retourne un JSON
        return new JsonResponse($jsonEleve, Response::HTTP_CREATED, ["Location" => $location], true);
    }


    //UPDATE
    /**
    * Cette méthode permet de modifier un élève selon son id.
    *
    * @OA\Response(
    *   response=201,
    *   description="Retourne l'élève modifié",
    *   @OA\JsonContent(
    *       type="array",
    *       @OA\Items(ref=@Model(type=Eleve::class,
    *       groups={"getEleve"}))
    *   )
    *  )
    *
    * @OA\Response(
    *   response=400,
    *   description="Mauvaise requête",
    *  )
    *
    *  @OA\Parameter(
    *      name="id",
    *      in="path",
    *      description="L'id de l'élève que l'on veut modifier",
    *      @OA\Schema(type="string")
    * )
    * @OA\RequestBody(
    *     required=true,
    *     @OA\JsonContent(
    *         example={
    *             "nom": "Dugardin",
    *             "prenom":"Jean",
    *             "moyenne":20,
    *             "idProf":102
    *         },
    *         @OA\Schema (
    *              type="object",
    *              @OA\Property(property="nom", required=true, description="Nom de l'élève", type="string"),
    *              @OA\Property(property="prenom", required=true, description="Prénom de l'élève", type="string"),
    *              @OA\Property(property="moyenne", required=true, description="Moyenne de l'élève", type="float"),
    *              @OA\Property(property="idProf", required=false, description="L'identifiant du professeur de l'élève", type="integer"),
    *         )
    *     )
    * )
    * @OA\Tag(name="Eleve")
    * @param Eleve $currentEleve
    * @param Request $request
    * @param SerializerInterface $serializer
    * @param EntityManagerInterface $em
    * @param TagAwareCacheInterface $cache
    * @param ProfesseurRepository $profRepo
    * @param ValidatorInterface $validator
    * @return JsonResponse
    */
    #[Route('api/eleve/{id}', name: 'app_update_eleve', methods:['PUT'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour modifier un élève ')]
    public function updateEleve(Request $request, SerializerInterface $serializer, Eleve $currentEleve, EntityManagerInterface $em, 
    ProfesseurRepository $profRepo, ValidatorInterface $validator, TagAwareCacheInterface $cache): JsonResponse{
        
        $newEleve = $serializer->deserialize($request->getContent(), Eleve::class, 'json');

        $currentEleve->setNom($newEleve->getNom());
        $currentEleve->setPrenom($newEleve->getPrenom());
        $currentEleve->setMoyenne($newEleve->getMoyenne());

        //Vérification des erreurs 
        $errors = $validator->validate($currentEleve);
        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        $content = $request->toArray();
        $IdProf = $content['idProf'] ?? -1;
        $currentEleve->setProfesseur($profRepo->find($IdProf));

        //Prépare la requête
        $em->persist($currentEleve);
        //Envoie dans la BDD
        $em->flush();
        
        $cache->invalidateTags(["eleveCache"]);
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }


}