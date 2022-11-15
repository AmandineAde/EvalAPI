<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Eleve;
use App\Entity\Classe;
use App\Entity\Professeur;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private $userPasswordHasher;
    
    public function __construct(UserPasswordHasherInterface $userPasswordHasher)
    {
        $this->userPasswordHasher = $userPasswordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // Création d'un user "normal"
        $user = new User();
        $user->setEmail("user@school.com");
        $user->setRoles(["ROLE_USER"]);
        $user->setPassword($this->userPasswordHasher->hashPassword($user, "password"));
        $manager->persist($user);
        
        // Création d'un user admin
        $userAdmin = new User();
        $userAdmin->setEmail("admin@school.com");
        $userAdmin->setRoles(["ROLE_ADMIN"]);
        $userAdmin->setPassword($this->userPasswordHasher->hashPassword($userAdmin, "password"));
        $manager->persist($userAdmin);

        // Création de 20 professeur
        $listProf =[];
        for ($i = 0; $i < 20; $i++) {
            $prof = new Professeur;
            $prof->setNom('Nom :' . $i);
            $prof->setPrenom('Prénom :'. $i);
            $manager->persist($prof);
            $listProf[]=$prof;
        }
        
        // Création de 20 élève
        for ($i = 0; $i < 20; $i++) {
            $eleve = new Eleve;
            $eleve->setNom('Nom :' . $i);
            $eleve->setPrenom('Prénom :'. $i);
            $eleve->setMoyenne($i);
            $manager->persist($eleve);
            $eleve->setProfesseur($listProf[$i]);//Viens prendre un prof aléatoirement
        }

        
        // Création de 20 classe
        for ($i = 0; $i < 20; $i++) {
            $classe = new Classe;
            $classe->setNom('Nom : ' . $i);

            $classe->setProfesseur($listProf[$i]);//Viens prendre un prof aléatoirement
            $manager->persist($classe);
        }

        $manager->flush();

    }
}
