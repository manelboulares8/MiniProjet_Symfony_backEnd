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

    #[Route('/{id}', name: 'app_commande_show', methods: ['GET'])]
    public function show(Commande $commande): JsonResponse
    {
        return $this->json($commande, Response::HTTP_OK, [], ['groups' => 'commande:read']);
    }

    #[Route('', name: 'app_commande_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
    
        // Validation des données
        if (!isset($data['article']) || !isset($data['quantite'])) {
            return $this->json(['message' => 'Données manquantes'], Response::HTTP_BAD_REQUEST);
        }
    
        $article = $em->getRepository(Article::class)->find($data['article']);
        if (!$article) {
            return $this->json(['message' => 'Article non trouvé'], Response::HTTP_NOT_FOUND);
        }
    
        // Vérification du stock
        if ($article->getStock() < $data['quantite']) {
            return $this->json(
                ['message' => 'Stock insuffisant. Quantité disponible: ' . $article->getStock()],
                Response::HTTP_BAD_REQUEST
            );
        }
    
        // Calcul du total
        $total = $article->getPrix() * $data['quantite'];
    
        // Création de la commande
        $commande = new Commande();
        $commande->setArticle($article);
        $commande->setQuantite($data['quantite']);
        $commande->setStatus($data['status'] ?? 'en cours');
        $commande->setDateCommande(new \DateTime());
        $commande->setTotal($total); // Assurez-vous d'avoir cette propriété dans l'entité Commande
    
        // Mise à jour du stock
        $article->setStock($article->getStock() - $data['quantite']);
    
        $em->persist($commande);
        $em->persist($article);
        $em->flush();
    
        return $this->json([
            'message' => 'Commande créée avec succès',
            'commande' => $commande,
            'total' => $total
        ], Response::HTTP_CREATED, [], ['groups' => 'commande:read']);
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
}