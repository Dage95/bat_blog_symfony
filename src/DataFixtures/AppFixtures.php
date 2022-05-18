<?php

namespace App\DataFixtures;

use App\Entity\Article;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class AppFixtures extends Fixture
{
    /**
     * Stockage du service d'encodage des mots de passe de Symfony
     */
    private $encoder;
    private $slugger;


    /**
     * On utilise le constructeur pour demander à Symfony de récupérer le sercvice d'encodage fdes mots de passe, pour ensuite le stocker dans $this->encoder
     */
    public function __construct(UserPasswordHasherInterface $encoder, SluggerInterface $slugger){
        $this->encoder = $encoder;
        $this->slugger = $slugger;
    }


    public function load(ObjectManager $manager): void
    {
        // Instanciation du Faker en fr
        $faker = Faker\Factory::create("fr_FR");

        $admin = new User();

        $admin
            ->setEmail("a@a.a")
            ->setRegistrationDate($faker->dateTimeBetween("-5 year", "now"))
            ->setPseudonym("Batman")
            ->setRoles(["ROLE_ADMIN"])
            ->setPassword(
                $this->encoder->hashPassword($admin, "aaaaaaaA11")
            )
        ;

        $manager->persist($admin);

        // Création de 10 comptes utilisateurs avec une boucle
        for($i = 0; $i < 10; $i++){
            $user = new User();

            $user
                ->setEmail($faker->email)
                ->setRegistrationDate($faker->dateTimeBetween("-4 year", "now"))
                ->setPseudonym($faker->userName)
                ->setPassword(
                    $this->encoder->hashPassword($user, "aaaaaaaA11")
                )
            ;

            $manager->persist($user);

        }

        // Création de 200 articles with boucle
        for($i = 0; $i < 200; $i++){

            $article = new Article();

            $article
                ->setTitle($faker->sentence(10))
                ->setContent($faker->paragraph(15))
                ->setPublicationDate($faker->dateTimeBetween("-4 year", "now"))
                ->setAuthor($admin) //l'admin batman
                ->setSlug($this->slugger->slug($article->getTitle())->lower())
            ;

            $manager->persist($article);

        }

        $manager->flush();

    }
}
