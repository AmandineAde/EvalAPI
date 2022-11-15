<?php

namespace App\Controller;

use App\Entity\Professeur;
use JMS\Serializer\Serializer;
use App\Repository\EleveRepository;
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

class ProfesseurController extends AbstractController
{

    //CRUD

    //GETALL
     /**
     * Cette méthode permet de récupérer l'ensemble des professeur(e)s.
     * 
     * @OA\Response(
     *      response=200,
     *      description="Retourne la liste des professeur(e)",
     *      @OA\JsonContent(
     *          type="array",
     *          @OA\Items(ref=@Model(type=Professeur::class, groups={"getProf"}))
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
     * @OA\Tag(name="Professeur(e)")
     * 
     * @param ProfesseurRepository $profRepo
     * @param SerializerInterface $serializer
     * @param Request $request
     * @param TagAwareCacheInterface $cachePool
     * @param VersioningService $versioningService
     * @param Security $security
     * @return JsonResponse
     */
    #[Route('api/professeur', name: 'app_professeur', methods:['GET'])]
    public function getAllProf(VersioningService $versioningService, Security $security, ProfesseurRepository $profRepo, SerializerInterface $serializer,  Request $request, TagAwareCacheInterface $cachePool): JsonResponse
    {

        $page = $request->get('page', 1);
        $limit = $request->get('limit', 10);

        $idCache = "getProfesseur-" . $page . "-" . $limit."-".implode(',', $security->getUser()->getRoles());
        $jsonProfList = $cachePool->get($idCache, function(ItemInterface $item) use ($profRepo, $page, $limit, $serializer, $versioningService) {
            $item->tag("professeurCache");
            $profList = $profRepo->findAllWithPagination($page, $limit);

            $version = $versioningService->getVersion();
            $context = SerializationContext::create()->setGroups(['getProf']);
            $context->setVersion($version);
            return $serializer->serialize($profList, 'json', $context);
        });
        return new JsonResponse($jsonProfList, Response::HTTP_OK, [], true);     
    }

    //DETAIL
    /**
     * Cette méthode permet de récupérer un seul professeur(e) selon son id.
     * 
     * @OA\Response(
     *      response=200,
     *      description="Retourne le professeur(e) demandé",
     *      @OA\JsonContent(
     *          type="array",
     *          @OA\Items(ref=@Model(type=Professeur::class, groups={"getProf"}))
     *      )
     * )
     * @OA\Parameter(
     *      name="id",
     *      in="path",
     *      description="L'id du professeur(e) que l'on veut retourner",
     *      @OA\Schema(type="string")
     * )
     * 
     * @OA\Tag(name="Professeur(e)")
     * 
     * @param Professeur $prof
     * @param SerializerInterface $serializer
     * @param VersioningService $versioningService
     * @return JsonResponse
     */
    #[Route('api/professeur/{id}', name: 'app_detail_professeur',methods:['GET'])]
    public function getDetailProf(VersioningService $versioningService,Professeur $prof, SerializerInterface $serializer): JsonResponse
    {
    
    $version = $versioningService->getVersion();
    $context = SerializationContext::create()->setGroups(['getProf']);
    $context->setVersion($version);
    $jsonProf = $serializer->serialize($prof, 'json', $context);
    return new JsonResponse($jsonProf, Response::HTTP_OK, [],true);
    }

    //DELETE
    /**
     * Cette méthode permet de supprimer un seul professeur(e) selon son id.
     * 
     * @OA\Response(
     *      response=204,
     *      description="Supprime le Professeur(e) demandé",
     *      @OA\JsonContent(
     *          type="array",
     *         @OA\Items(type="boolean")
     *      )
     * )
     * @OA\Parameter(
     *      name="id",
     *      in="path",
     *      description="L'id du Professeur(e) que l'on veut supprimer",
     *      @OA\Schema(type="string")
     * )
     * 
     * @OA\Tag(name="Professeur(e)")
     * 
     * @param Professeur $professeur
     * @param EntityManagerInterface $em
     * @param TagAwareCacheInterface $cachePool
     * @return JsonResponse
     */
    #[Route('api/professeur/{id}', name: 'app_delete_professeur',methods:['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour supprimer un professeur ')]
    public function deleteProf(Professeur $professeur, EntityManagerInterface $em, TagAwareCacheInterface $cachePool): JsonResponse{
        $cachePool->invalidateTags(["professeurCache"]);

        foreach ($professeur->getEleves() as $eleve) {
            $em->remove($eleve, true);
        }
        $em->remove($professeur);
        $em->flush();
        
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }


    //CREATE
    /**
    * Cette méthode permet de créer un professeur(e)
    *
    * @OA\Response(
    *   response=201,
    *   description="Retourne le professeur(e) créer",
    *   @OA\JsonContent(
    *       type="array",
    *       @OA\Items(ref=@Model(type=Professeur::class,
    *       groups={"getProf"}))
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
    *             "nom": "Lamy",
    *             "prenom": "Alexandra",
    *             "idClasse": 85,
    *             "idEleve": 67
    *         },
    *         @OA\Schema (
    *              type="object",
    *              @OA\Property(property="nom", required=true, description="Nom du professeur(e)", type="string"),
    *              @OA\Property(property="prenom", required=true, description="Prénom du professeur(e)", type="string"),
    *              @OA\Property(property="idClasse", required=false, description="L'identifiant de la classe du professeur(e)", type="integer"),
    *              @OA\Property(property="idEleve", required=false, description="L'identifiant de l'élève du professeur(e)", type="integer")
    *         )
    *     )
    * )
    * @OA\Tag(name="Professeur(e)")
    * @param VersioningService $versioningService
    * @param Request $request
    * @param SerializerInterface $serializer
    * @param EntityManagerInterface $em
    * @param UrlGeneratorInterface $urlGenerator
    * @param ClasseRepository $classeRepo
    * @param EleveRepository $eleveRepo
    * @param ValidatorInterface $validator
    * @return JsonResponse
    */
    #[Route('api/professeur', name:"app_create_professeur", methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour créer un professeur ')]
    public function createProf(VersioningService $versioningService,Request $request, SerializerInterface $serializer, EntityManagerInterface $em,
     UrlGeneratorInterface $urlGenerator, ClasseRepository $classeRepo, EleveRepository $eleveRepo, ValidatorInterface $validator): JsonResponse
    {

        $prof = $serializer->deserialize($request->getContent(), Professeur::class, 'json');
            
        $content = $request->toArray();

        if (gettype($content['idClasse']) == "array") {
            foreach ($content['idClasse'] as $classeEnt) {
                $prof->addClasse($classeRepo->find($classeEnt));
            }
        } elseif (gettype($content['idClasse']) == "integer") {
            $prof->addClasse($classeRepo->find($content['idClasse']));
        }

        if (gettype($content['idEleve']) == "array") {
            foreach ($content['idEleve'] as $eleve) {
                $prof->addEleve($eleveRepo->find($eleve));
            }
        } elseif (gettype($content['idEleve']) == "integer") {
            $prof->addEleve($eleveRepo->find($content['idEleve']));
        }

        $errors = $validator->validate($prof);
        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors,
            'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }
        
        $em->persist($prof);
        $em->flush();

        $version = $versioningService->getVersion();
        $context = SerializationContext::create()->setGroups(['getProf']);
        $context->setVersion($version);
        $jsonProf = $serializer->serialize($prof, 'json', $context);
        $location = $urlGenerator->generate('app_detail_professeur', ['id' => $prof->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        return new JsonResponse($jsonProf, Response::HTTP_CREATED, ["Location" => $location], true);
    }

    //UPDATE
    /**
    * Cette méthode permet de modifier un professeur(e) selon son id.
    *
    * @OA\Response(
    *   response=201,
    *   description="Retourne le professeur(e) modifié(e)",
    *   @OA\JsonContent(
    *       type="array",
    *       @OA\Items(ref=@Model(type=Professeur::class,
    *       groups={"getProf"}))
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
    *      description="L'id du professeur(e) que l'on veut modifier",
    *      @OA\Schema(type="string")
    * )
    * @OA\RequestBody(
    *     required=true,
    *     @OA\JsonContent(
    *         example={
    *             "nom": "Lamy",
    *             "prenom": "Alexandra",
    *             "idClasse": 85,
    *             "idEleve": 67
    *         },
    *         @OA\Schema (
    *              type="object",
    *              @OA\Property(property="nom", required=true, description="Nom du professeur(e)", type="string"),
    *              @OA\Property(property="prenom", required=true, description="Prénom du professeur(e)", type="string"),
    *              @OA\Property(property="idClasse", required=false, description="L'identifiant de la classe du professeur(e)", type="integer"),
    *              @OA\Property(property="idEleve", required=false, description="L'identifiant de l'élève du professeur(e)", type="integer")
    *         )
    *     )
    * )
    * @OA\Tag(name="Professeur(e)")
    * @param Professeur $currentProf
    * @param Request $request
    * @param SerializerInterface $serializer
    * @param EntityManagerInterface $em
    * @param TagAwareCacheInterface $cache
    * @param ClasseRepository $classeRepo
    * @param EleveRepository $eleveRepo
    * @param ValidatorInterface $validator
    * @return JsonResponse
    */
    #[Route('api/professeur/{id}', name: 'app_update_professeur', methods:['PUT'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour modifier un professeur ')]
    public function updateProf(Request $request, SerializerInterface $serializer, Professeur $currentProf, EntityManagerInterface $em, 
    ClasseRepository $classeRepo, EleveRepository $eleveRepo, ValidatorInterface $validator,TagAwareCacheInterface $cache): JsonResponse{
        $newProf = $serializer->deserialize($request->getContent(), Professeur::class, 'json');
        $currentProf->setNom($newProf->getNom());
        $currentProf->setPrenom($newProf->getPrenom());

        $content = $request->toArray();

        if (gettype($content['idClasse']) == "array") {
            foreach ($content['idClasse'] as $idClasse) {
                $currentProf->addClasse($classeRepo->find($idClasse));
            }
        } elseif (gettype($content['idClasse']) == "integer") {
            $currentProf->addClasse($classeRepo->find($content['idClasse']));
        }

        if (gettype($content['idEleve']) == "array") {
            foreach ($content['idEleve'] as $idEleve) {
                $currentProf->addEleve($eleveRepo->find($idEleve));
            }
        } elseif (gettype($content['idEleve']) == "integer") {
            $currentProf->addEleve($eleveRepo->find($content['idEleve']));
        }

        $errors = $validator->validate($currentProf);
        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        $em->persist($currentProf);
        $em->flush();

        $cache->invalidateTags(["professeurCache"]);
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
