<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Entity\Article;
use App\Repository\CommandeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use App\Repository\ClientRepository;
use Symfony\Component\HttpFoundation\Session\SessionInterface;


#[Route('/api/commandes')]
final class CommandeController extends AbstractController
{
    public function __construct(
        private SerializerInterface $serializer
    ) {
    }

      #[Route('', name: 'app_commande_index', methods: ['GET'])]
     public function index(CommandeRepository $commandeRepository): JsonResponse
     {
         $commandes = $commandeRepository->findAll();
         return $this->json($commandes, Response::HTTP_OK, [], ['groups' => 'commande:read']);
     }
   /* #[Route('', name: 'app_commande_index', methods: ['GET'])]
    public function index(CommandeRepository $commandeRepository): JsonResponse
    {
        $commandes = $commandeRepository->findAllWithClient(); // Utilisez une méthode custom
        return $this->json($commandes, Response::HTTP_OK, [], ['groups' => 'commande:read']);
    }*/

    #[Route('/{id}', name: 'app_commande_show', methods: ['GET'])]
    public function show(Commande $commande): JsonResponse
    {
        return $this->json($commande, Response::HTTP_OK, [], ['groups' => 'commande:read']);
    }


    #[Route('', name: 'api_commande_add', methods: ['POST'])]
    public function addCommande(Request $request, EntityManagerInterface $em, ClientRepository $clientRepo): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Récupération de l'ID client depuis les données de la requête
        if (!isset($data['clientId'])) {
            return $this->json(
                ['error' => 'Client ID is required'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $client = $clientRepo->find($data['clientId']);
        if (!$client) {
            return $this->json(
                ['error' => 'Client not found'],
                Response::HTTP_NOT_FOUND
            );
        }

        // Validation des données
        if (!isset($data['article']) || !isset($data['quantite'])) {
            return $this->json(
                ['error' => 'Missing required fields: article and quantite'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $article = $em->getRepository(Article::class)->find($data['article']);
        if (!$article) {
            return $this->json(
                ['error' => 'Article not found'],
                Response::HTTP_BAD_REQUEST
            );
        }

        try {
            $commande = new Commande();
            $commande->setQuantite($data['quantite']);
            $commande->setDateCommande(new \DateTime());
            $commande->setStatus('en cours');
            $commande->setTotal($article->getPrix() * $data['quantite']);
            $commande->setArticle($article);
            $commande->setClient($client);

            $em->persist($commande);
            $em->flush();

            return $this->json(
                $commande,
                Response::HTTP_CREATED,
                [],
                ['groups' => 'commande:read']
            );
        } catch (\Exception $e) {
            return $this->json(
                ['error' => 'Server error: ' . $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[Route('/{id}', name: 'app_commande_update', methods: ['PUT'])]
    public function update(Request $request, Commande $commande, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (isset($data['quantite'])) {
            $commande->setQuantite($data['quantite']);
        }
        if (isset($data['status'])) {
            $commande->setStatus($data['status']);
        }
        if (isset($data['articleId'])) {
            $article = $em->getRepository(Article::class)->find($data['articleId']);
            if (!$article) {
                return $this->json(['error' => 'Article non trouvé'], Response::HTTP_BAD_REQUEST);
            }
            $commande->setArticle($article);
        }
        $commande->setTotal(total: $article->getPrix() * $data['quantite']);

        $em->flush();

        return $this->json($commande, Response::HTTP_OK, [], ['groups' => 'commande:read']);
    }

    #[Route('/{id}', name: 'app_commande_delete', methods: ['DELETE'])]
    public function delete(Commande $commande, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($commande);
        $em->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/client/{clientId}', name: 'app_commande_by_client', methods: ['GET'])]
    public function getByClient(int $clientId, CommandeRepository $commandeRepository, ClientRepository $clientRepo): JsonResponse
    {
        $client = $clientRepo->find($clientId);

        if (!$client) {
            return $this->json(['error' => 'Client not found'], Response::HTTP_NOT_FOUND);
        }

        $commandes = $commandeRepository->findBy(['client' => $client]);

        return $this->json($commandes, Response::HTTP_OK, [], ['groups' => 'commande:read']);
    }

    #[Route('/{id}/client-name', name: 'app_commande_client_name', methods: ['GET'])]
    public function getClientNameByCommande(Commande $commande): JsonResponse
    {
        if (!$commande->getClient()) {
            return $this->json(['error' => 'Client not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'clientName' => $commande->getClient()->getNom()
        ]);
    }
}