<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\ArticleRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use App\Entity\Article;
final class ArticleController extends AbstractController
{
    #[Route('/api/articles', name: 'app_articles')]
    public function index(ArticleRepository $repo): JsonResponse
    {
        $articles = $repo->findAll();

        $data = [];

        foreach ($articles as $article) {
            $data[] = [
                'id' => $article->getId(),
                'nom' => $article->getNom(),
                'description' => $article->getDescription(),
                'prix' => $article->getPrix(),
                'stock' => $article->getStock(),
            ];
        }

        return $this->json($data);
    }

    #[Route('/api/articles/add', name: 'api_articles_post', methods: ['POST'])]
    public function add(Request $request, EntityManagerInterface $em, SerializerInterface $serializer): JsonResponse
    {
        $article = $serializer->deserialize($request->getContent(), Article::class, 'json');
        $em->persist($article);
        $em->flush();

        return $this->json($article, 201, [], ['groups' => 'article:read']);
    }
    #[Route('/api/articles/{id}', name: 'get_article_by_id', methods: ['GET'])]
    public function getArticleById(int $id, ArticleRepository $articleRepository): JsonResponse
    {
        $article = $articleRepository->find($id);

        if (!$article) {
            return $this->json(['message' => 'Article non trouvé'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($article, Response::HTTP_OK, [], ['groups' => 'article:read']);
    }
    #[Route('/api/articles/{id}', name: 'update_article', methods: ['PUT'])]
    public function updateArticle(
        Request $request,
        ArticleRepository $articleRepository,
        EntityManagerInterface $entityManager,
        int $id
    ): JsonResponse {
        $article = $articleRepository->find($id);

        if (!$article) {
            return $this->json(['message' => 'Article non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        $article->setNom($data['nom'] ?? $article->getNom());
        $article->setPrix($data['prix'] ?? $article->getPrix());
        $article->setStock($data['stock'] ?? $article->getStock());
        $article->setDescription($data['description'] ?? $article->getDescription());

        $entityManager->flush();

        return $this->json($article, Response::HTTP_OK, [], ['groups' => 'article:read']);
    }
    #[Route('/api/articles/{id}', name: 'delete_article', methods: ['DELETE'])]
    public function deleteArticle(EntityManagerInterface $em, ArticleRepository $articleRepository, int $id): JsonResponse
    {
        $article = $articleRepository->find($id);

        if (!$article) {
            return new JsonResponse(['message' => 'Article non trouvé'], JsonResponse::HTTP_NOT_FOUND);
        }

        $em->remove($article);
        $em->flush();

        return new JsonResponse(['message' => 'Article supprimé avec succès'], JsonResponse::HTTP_NO_CONTENT);
    }

}
