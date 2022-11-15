<?php

namespace App\Controller;

use App\Entity\Classe;
use App\Repository\ClasseRepository;
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



class ClasseController extends AbstractController
{
    
    
    //CRUD

    //GETALL
    //Récupère toutes les classes
     /**
     * Cette méthode permet de récupérer l'ensemble des classes.
     * 
     * @OA\Response(
     *      response=200,
     *      description="Retourne la liste des classes",
     *      @OA\JsonContent(
     *          type="array",
     *          @OA\Items(ref=@Model(type=Classe::class, groups={"getClasse"}))
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
     * @OA\Tag(name="Classe")
     * 
     * @param ClasseRepository $classeRepo
     * @param SerializerInterface $serializer
     * @param Request $request
     * @param TagAwareCacheInterface $cachePool
     * @param VersioningService $versioningService
     * @param Security $security
     * @return JsonResponse
     */
    #[Route('api/classe', name: 'app_classe', methods:['GET'])]
    public function getAllClasse(VersioningService $versioningService,Security $security, ClasseRepository $classeRepo, 
    SerializerInterface $serializer, Request $request, TagAwareCacheInterface $cachePool): JsonResponse
    {
        //création d'une pagination avec une limite de 10 par page
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 10);

        //
        $idCache = "getClasses-" . $page . "-" . $limit."-".implode(',', $security->getUser()->getRoles());
        $jsonClasseList = $cachePool->get($idCache, function(ItemInterface $item) use ($classeRepo, $page, $limit, $serializer,$versioningService) {
            $item->tag("classeCache");
            $classeList = $classeRepo->findAllWithPagination($page, $limit);

            $version = $versioningService->getVersion();

            $context = SerializationContext::create()->setGroups(['getClasse']);
            $context->setVersion($version);
            return $serializer->serialize($classeList, 'json', $context);
        });

        return new JsonResponse($jsonClasseList, Response::HTTP_OK, [], true);      
    }


    //DETAIL
    //récupère une seule classe selon son id 
    /**
     * Cette méthode permet de récupérer une seule classe selon son id.
     * 
     * @OA\Response(
     *      response=200,
     *      description="Retourne la classe demandée",
     *      @OA\JsonContent(
     *          type="array",
     *          @OA\Items(ref=@Model(type=Classe::class, groups={"getClasse"}))
     *      )
     * )
     * @OA\Parameter(
     *      name="id",
     *      in="path",
     *      description="L'id de la classe que l'on veut retourner",
     *      @OA\Schema(type="string")
     * )
     * 
     * @OA\Tag(name="Classe")
     * 
     * @param Classe $classe
     * @param SerializerInterface $serializer
     * @param VersioningService $versioningService
     * @return JsonResponse
     */
    #[Route('api/classe/{id}', name: 'app_detail_classe',methods:['GET'])]
    public function getDetailClasse(VersioningService $versioningService,Classe $classe, SerializerInterface $serializer): JsonResponse
    {
        //Utilisation de la version 
        $version = $versioningService->getVersion();
        $context = SerializationContext::create()->setGroups(['getClasse']);
        $jsonClasse = $serializer->serialize($classe, 'json', $context);
        $context->setVersion( $version);
        return new JsonResponse($jsonClasse, Response::HTTP_OK, [],true);
    }  

    //DELETE
    //Supprime une classe selon son id
    /**
     * Cette méthode permet de supprimer une seule classe selon son id.
     * 
     * @OA\Response(
     *      response=204,
     *      description="Supprime la classe demandée",
     *      @OA\JsonContent(
     *          type="array",
     *         @OA\Items(type="boolean")
     *      )
     * )
     * @OA\Parameter(
     *      name="id",
     *      in="path",
     *      description="L'id de la classe que l'on veut supprimer",
     *      @OA\Schema(type="string")
     * )
     * 
     * @OA\Tag(name="Classe")
     * 
     * @param Classe $classe
     * @param EntityManagerInterface $em
     * @param TagAwareCacheInterface $cachePool
     * @return JsonResponse
     */
    #[Route('api/classe/{id}', name: 'app_delete_classe',methods:['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour supprimer une classe ')]
    public function deleteClasse(Classe $classe, EntityManagerInterface $em, TagAwareCacheInterface $cachePool): JsonResponse{
        $cachePool->invalidateTags(["classeCache"]);

        //supprime la classe
        $em->remove($classe);
        $em->flush();
        
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    //CREATE
    //Fonction pour créer une classe
    /**
    * Cette méthode permet de créer une clase.
    *
    * @OA\Response(
    *   response=201,
    *   description="Retourne la classe créée",
    *   @OA\JsonContent(
    *       type="array",
    *       @OA\Items(ref=@Model(type=Classe::class,
    *       groups={"getClasse"}))
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
    *             "nom": "6ème",
    *             "idProf": 100
    *         },
    *         @OA\Schema (
    *              type="object",
    *              @OA\Property(property="nom", required=true, description="Nom de la classe", type="string"),
    *              @OA\Property(property="idProf", required=false, description="L'identifiant du professeur de la classe", type="integer"),
    *         )
    *     )
    * )
    * @OA\Tag(name="Classe")
    * @param VersioningService $versioningService
    * @param Request $request
    * @param SerializerInterface $serializer
    * @param EntityManagerInterface $em
    * @param UrlGeneratorInterface $urlGenerator
    * @param ProfesseurRepository $profRepo
    * @param ValidatorInterface $validator
    * @return JsonResponse
    */
    #[Route('/api/classe', name:"app_create_classe", methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour créer une classe ')]
    public function createClasse(VersioningService $versioningService,ValidatorInterface $validator, Request $request,
     SerializerInterface $serializer, EntityManagerInterface $em, UrlGeneratorInterface $urlGenerator, ProfesseurRepository $profRepo): JsonResponse
    {
        
        $classe = $serializer->deserialize($request->getContent(), Classe::class, 'json');

        $content = $request->toArray();
        $idProf = $content['idProf'] ?? -1;
        $classe->setProfesseur($profRepo->find($idProf));

        $errors = $validator->validate($classe);
        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors,
            'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        // if(gettype($idProf) != "integer") {
        //     $em->persist($classe->getProfesseur());
        // } else {
        //     $classe->setAuthor($profRepo->find($idProf));
        // }

        $em->persist($classe);
        $em->flush();

        $version = $versioningService->getVersion();
        $context = SerializationContext::create()->setGroups(['getClasse']);
        $context->setVersion($version);
        $jsonClasse = $serializer->serialize($classe, 'json', $context);

        $location = $urlGenerator->generate('app_detail_classe', ['id' => $classe->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        return new JsonResponse($jsonClasse, Response::HTTP_CREATED, ["Location" => $location], true);
    }
    
    //UPDATE
    /**
    * Cette méthode permet de modifier une classe selon son id.
    *
    * @OA\Response(
    *   response=201,
    *   description="Retourne la classe modifiée",
    *   @OA\JsonContent(
    *       type="array",
    *       @OA\Items(ref=@Model(type=Classe::class,
    *       groups={"getClasse"}))
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
    *      description="L'id de la classe que l'on veut modifier",
    *      @OA\Schema(type="string")
    * )
    * @OA\RequestBody(
    *     required=true,
    *     @OA\JsonContent(
    *         example={
    *             "nom": "6ème",
    *             "idProf": 100
    *         },
    *         @OA\Schema (
    *              type="object",
    *              @OA\Property(property="nom", required=true, description="Nom de la classe", type="string"),
    *              @OA\Property(property="idProf", required=false, description="L'identifiant du professeur de la classe", type="integer"),
    *         )
    *     )
    * )
    * @OA\Tag(name="Classe")
    * @param Classe $currentClasse
    * @param Request $request
    * @param SerializerInterface $serializer
    * @param EntityManagerInterface $em
    * @param TagAwareCacheInterface $cache
    * @param ProfesseurRepository $profRepo
    * @param ValidatorInterface $validator
    * @return JsonResponse
    */
    #[Route('api/classe/{id}', name: 'app_update_classe', methods:['PUT'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour modifier une classe ')]
    public function updateClasse(Request $request, SerializerInterface $serializer, Classe $currentClasse, EntityManagerInterface $em, 
    ProfesseurRepository $profRepo, ValidatorInterface $validator, TagAwareCacheInterface $cache): JsonResponse{
        $newClasse = $serializer->deserialize($request->getContent(), Classe::class, 'json');
        $currentClasse->setNom($newClasse->getNom());

        $content = $request->toArray();
        $idProf = $content['idProf'] ?? -1;
        $currentClasse->setProfesseur($profRepo->find($idProf));

        $errors = $validator->validate($currentClasse);
        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        $em->persist($currentClasse);
        $em->flush();

        $cache->invalidateTags(["classeCache"]);
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }
    
}
