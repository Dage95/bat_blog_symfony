<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Comment;
use App\Form\AddCommentFormType;
use App\Form\NewArticleFormType;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route("/blog", name:"blog_")]
class BlogController extends AbstractController
{
    /**
     * Contrôleur de la page permettant de créer un nouvel article
     *
     * Accès reservé aux administrateurs (ROLE_ADMIN)
     */
    #[Route("/nouvelle-publication/", name:"new_publication")]
    #[IsGranted("ROLE_ADMIN")]
    public function newPublication(Request $request, ManagerRegistry $doctrine, SluggerInterface $slugger): Response
    {
        $article = new Article();

        $form = $this->createForm(NewArticleFormType::class, $article);

        $form->handleRequest($request);

        // Si le formulaire est bien envoyé et sans erreur
        if ($form->isSubmitted() && $form->isValid()) {

            // On termine d'hydrater
            $article
                ->setPublicationDate(new \DateTime())
                ->setAuthor($this->getUser())
                ->setSlug($slugger->slug($article->getTitle())->lower());

            // Sauvergarde en BDD via le manager général des entités de Doctrine
            $em = $doctrine->getManager();
            $em->persist($article);
            $em->flush();

            // Message flash de succès
            $this->addFlash("success", "Article publié avec succès !");

            return $this->redirectToRoute("blog_publication_view", [
                "id"=>$article->getId(),
                "slug"=>$article->getSlug()
            ]);

        }

        return $this->render("blog/new_publication.html.twig", [
            "form" => $form->createView(),
        ]);

    }


    /**
     * Contrôleur de la page permettant de voir un article en détail (via id et slug dans l'url
     */
    #[Route("/publication/{id}/{slug}", name: "publication_view")]
    #[ParamConverter("article", options: ["mapping" => ["id"=>"id", "slug"=>"slug"]])]
    public function publicationView(Article $article, Request $request, ManagerRegistry $doctrine): Response
    {

        // Si l'utilisateur n'est pas connecté, appel direct de la vue en lui envoyant seulement l'article à afficher
        // On le fait pour éviter que le traitement du formulaire ne se fasse alors que la personne n'est pas connecté.
        if(!$this->getUser()){
            return $this->render("blog/publication_view.html.twig", [
                "article"=>$article,
            ]);
        }

        $comment = new Comment;

        $form = $this->createForm(AddCommentFormType::class, $comment);

        $form->handleRequest($request);

        // Si le formulaire est envoyé et sans erreur
        if ($form->isSubmitted() && $form->isValid()){

            // Hydratation
            $comment
                ->setPublicationDate(new \DateTime())
                ->setAuthor($this->getUser())
                ->setArticle($article)
            ;

            // Sauvegarde en BDD
            $em = $doctrine->getManager();
            $em->persist($comment);
            $em->flush();

            // Message flash de succès
            $this->addFlash("success", "Votre commentaire à été publié avec succès !");

            // Réinitialisation des variables $form et $comment pour un nouveau formulaire vierge
            unset($comment);
            unset($form);

            $comment = new Comment;
            $form = $this->createForm(AddCommentFormType::class, $comment);

        }

        return $this->render("blog/publication_view.html.twig", [
            "article"=>$article,
            "form"=> $form->createView(),
        ]);

    }


    /**
     * Contrôleur de la page admin servant a supprimer un commentaire via son id dans l'url
     *
     * Accès réservé aux admins
     */
    #[Route("/commentaire/suppression/{id}/", name:"comment_delete")]
    #[IsGranted("ROLE_ADMIN")]
    public function commentDelete(Comment $comment, Request $request, ManagerRegistry $doctrine): Response
    {


        // Si le token csrf passé dans l'rul n'est pas valide
        if(!$this->isCsrfTokenValid("blog_comment_delete_" . $comment->getId(), $request->query->get("csrf_token"))){

            $this->addFlash("error", "Token de sécurité invalide. Veuillez ré-essayer");

        } else {

            // Suppression du commentaire en BDD
            $em = $doctrine->getManager();
            $em->remove($comment);
            $em->flush();

            // Message flash de succès
            $this->addFlash("success", "Le commentaire à été supprimé avec succès !");

        }

        // Redirection
        return $this->redirectToRoute("blog_publication_view", [
            "id"=> $comment->getArticle()->getId(),
            "slug"=> $comment->getArticle()->getSlug(),
        ]);

    }


    /**
     * Contrôleur de la page qui liste les articles
     */
    #[Route("/publications/liste/", name:"publication_list")]
    public function publicationList(ManagerRegistry $doctrine, Request $request, PaginatorInterface $paginator): Response
    {
        // Recuperation de $_GET["page"], page 1 si elle n'existe pas
        $requestedPage = $request->query->getInt("page", 1);

        // Vérification que le nombre est positif
        if($requestedPage < 1){
            throw new NotFoundHttpException();
        }

        $em = $doctrine->getManager();

        $query = $em->createQuery("SELECT a FROM App\Entity\Article a ORDER BY a.publicationDate DESC");

        $articles = $paginator->paginate(
            $query,     // Requête créée juste avant
            $requestedPage,     // Page qu'on souhaite voir
            10,     // Nombre d'articles à afficher par page
        );

        return $this->render("blog/publication_list.html.twig", [
            "articles"=>$articles,
        ]);
    }


    /**
     * Contrôleur de la page admin servant à supprimer un article via son id dans l'url
     *
     * Accès réservé aux admins
     */
    #[Route("/publication/suppression/{id}/", name:"publication_delete", priority: 10)]
    #[IsGranted("ROLE_ADMIN")]
    public function publicationDelete(Article $article, Request $request, ManagerRegistry $doctrine): Response
    {
        $csrfToken = $request->query->get("csrf_token", "");

        if(!$this->isCsrfTokenValid("blog_publication_delete_" . $article->getId(), $csrfToken)){

            $this->addFlash("error", "Token de sécurité invalide. Veuillez ré-essayer !");

        }else{

            // Suppression de l'article en BDD
            $em = $doctrine->getManager();
            $em->remove($article);
            $em->flush();

            // Message flash de succès
            $this->addFlash("success", "L'article à été supprimé avec succès !");

        }

        // Redirection
        return $this->redirectToRoute("blog_publication_list");

    }


    /**
     * Contrôleur de la page permettant de modifier un  article existant via son id passé dans l'url
     *
     * Accès réservé aux administrateurs (ROLE_ADMIN)
     */
    #[Route("/publication/modifier/{id}/", name: "publication_edit", priority: 10)]
    #[IsGranted("ROLE_ADMIN")]
    public function publicationEdit(Article $article, Request $request, ManagerRegistry $doctrine, SluggerInterface $slugger): Response
    {

        // Instanciation d'un nouveau formulaire basé sur $article qui contient déjà les données actuelles de l'article à modifier
        $form = $this->createForm(NewArticleFormType::class, $article);

        $form->handleRequest($request);

        // Si le formulaire est envoyé et sans erreurs
        if($form->isSubmitted() && $form->isValid()){

            // Sauvegarde des données modifiées en BDD
            $article->setSlug($slugger->slug($article->getTitle())->lower());
            $em = $doctrine->getManager();
            $em->flush();

            // Message flash de succès
            $this->addFlash("success", "Publication modifiée avec succès");

            // Redirection vers l'article modifié
            return $this->redirectToRoute("blog_publication_view", [
                "id"=>$article->getId(),
                "slug"=>$article->getSlug(),
            ]);

        }

        return $this->render("blog/publication_edit.html.twig", [
            "form"=>$form->createView(),
        ]);

    }

    /**
     * Contrôleur de la page affichant les résultats des recherches faites par le formulaire de recherche dans la navbar
     */
    #[Route("/recherche/", name: "search")]
    public function search(ManagerRegistry $doctrine, Request $request, PaginatorInterface $paginator): Response
    {

        // Récupération de $_GET['page'], 1 si elle n'existe pas
        $requestedPage = $request->query->getInt('page', 1);

        // Vérification que le nombre est positif
        if($requestedPage < 1){
            throw new NotFoundHttpException();
        }

        // On récupère la recherche de l'utilisateur depuis l'URL ( $_GET['s'] )
        $search = $request->query->get('s', '');

        $em = $doctrine->getManager();

        // Création d'une requête qui récupèrera seulement les articles dont le titre ou le contenu contient le mot rechercher par l'utilisateur avec LIKE
        $query = $em
            ->createQuery('SELECT a FROM App\Entity\Article a WHERE a.title LIKE :search OR a.content LIKE :search ORDER BY a.publicationDate DESC')
            ->setParameters([
                'search' => '%' . $search . '%'
            ])
        ;

        $articles = $paginator->paginate(
            $query,     // Requête créée juste avant
            $requestedPage,     // Page qu'on souhaite voir
            10,     // Nombre d'article à afficher par page
        );

        return $this->render('blog/list_search.html.twig', [
            'articles' => $articles,
        ]);
    }

}
