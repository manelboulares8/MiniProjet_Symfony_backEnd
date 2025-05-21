<?php
namespace App\Controller;
use App\Entity\Client;
use App\Repository\ClientRepository;
use Doctrine\ORM\EntityManagerInterface;
use SebastianBergmann\Environment\Console;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
#[Route('/api/clients')]
class ClientController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator
    ) {
    }

    /*  #[Route('', name: 'app_client_index', methods: ['GET'])]
      public function index(ClientRepository $clientRepository): JsonResponse
      {
          $clients = $clientRepository->findAll();
          $data = $this->serializer->serialize($clients, 'json', ['groups' => 'client:read']);

          return new JsonResponse($data, Response::HTTP_OK, [], true);
      }*/
    #[Route('/all', name: 'app_client_index', methods: ['GET'])]
    public function index(ClientRepository $clientRepository): JsonResponse
    {
        $clients = $clientRepository->findAll();

        if (empty($clients)) {
            return $this->json(['message' => 'Aucun client trouvé'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($clients, Response::HTTP_OK, [], ['groups' => 'client:read']);
    }

    /*  #[Route('/{id}', name: 'app_client_show', methods: ['GET'])]
      public function show(Client $client): JsonResponse
      {
          $data = $this->serializer->serialize($client, 'json', [
              'groups' => ['client:read', 'client:details']
          ]);

          return new JsonResponse($data, Response::HTTP_OK, [], true);
      }*/
    /*
        #[Route('/api/clients', name: 'app_client_create', methods: ['POST'])]
        public function create(Request $request): JsonResponse
        {
            $client = $this->serializer->deserialize($request->getContent(), Client::class, 'json');

            $errors = $this->validator->validate($client);
            if (count($errors) > 0) {
                return $this->json($errors, Response::HTTP_BAD_REQUEST);
            }

            $this->em->persist($client);
            $this->em->flush();

            $data = $this->serializer->serialize($client, 'json', ['groups' => 'client:read']);

            return new JsonResponse($data, Response::HTTP_CREATED, [], true);
        }
    */
    #[Route('', name: 'app_client_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $client = new Client();
        $client->setNom($data['nom'] ?? '');
        $client->setEmail($data['email'] ?? '');
        $client->setAdresse($data['adresse'] ?? '');

        $errors = $this->validator->validate($client);
        if (count($errors) > 0) {
            return $this->json($errors, Response::HTTP_BAD_REQUEST);
        }

        $this->em->persist($client);
        $this->em->flush();

        return $this->json($client, Response::HTTP_CREATED);
    }
    #[Route('/{id}', name: 'app_client_update', methods: ['PUT'])]
    public function update(Request $request, Client $client): JsonResponse
    {
        $this->serializer->deserialize(
            $request->getContent(),
            Client::class,
            'json',
            ['object_to_populate' => $client]
        );

        $errors = $this->validator->validate($client);
        if (count($errors) > 0) {
            return $this->json($errors, Response::HTTP_BAD_REQUEST);
        }

        $this->em->flush();

        $data = $this->serializer->serialize($client, 'json', ['groups' => 'client:read']);

        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }

    #[Route('/{id}', name: 'app_client_delete', methods: ['DELETE'])]
    public function delete(Client $client): JsonResponse
    {
        $this->em->remove($client);
        $this->em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/{id}/commandes', name: 'app_client_commandes', methods: ['GET'])]
    public function getCommandes(Client $client): JsonResponse
    {
        $commandes = $client->getCommandes();
        $data = $this->serializer->serialize($commandes, 'json', [
            'groups' => ['commande:read']
        ]);

        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }


    #[Route('/login', name: 'api_client_login', methods: ['POST'])]
    public function login(Request $request, ClientRepository $clientRepository, SessionInterface $session): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';

        $client = $clientRepository->findOneBy(['email' => $email]);

        if (!$client || $client->getPassword() !== $password) {
            return new JsonResponse(['error' => 'Invalid credentials'], 401);
        }

        // Stockage de l'ID client dans la session
        $session->set('client_id', $client->getId());

        return new JsonResponse([
            'id' => $client->getId(),
            'nom' => $client->getNom(),
            'role' => $client->getRole(),
            'email' => $client->getEmail()
        ]);
    }
    #[Route('/register', name: 'app_client_register', methods: ['POST'])]
    public function register(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Validation
        if (!isset($data['nom']) || !isset($data['email']) || !isset($data['password']) || !isset($data['adresse'])) {
            return $this->json(['error' => 'Tous les champs sont obligatoires'], Response::HTTP_BAD_REQUEST);
        }

        // Vérifier si l'email existe déjà
        $existingClient = $em->getRepository(Client::class)->findOneBy(['email' => $data['email']]);
        if ($existingClient) {
            return $this->json(['error' => 'Cet email est déjà utilisé'], Response::HTTP_CONFLICT);
        }

        // Créer le client
        $client = new Client();
        $client->setNom($data['nom']);
        $client->setEmail($data['email']);
        $client->setAdresse($data['adresse']);
        $client->setRole('user');

        // Hasher le mot de passe
        //   $hashedPassword = $passwordHasher->hashPassword($client, $data['password']);
        $client->setPassword($data['password']);

        $em->persist($client);
        $em->flush();

        return $this->json([
            'id' => $client->getId(),
            'nom' => $client->getNom(),
            'email' => $client->getEmail(),
            'role' => $client->getRole()
        ], Response::HTTP_CREATED);
    }

    #[Route('/list-users', name: 'app_clients_list_users', methods: ['GET'])]
    public function listUsers(ClientRepository $repository): JsonResponse
    {
        // Debug: Vérifiez que la méthode est appelée
        error_log("Endpoint /api/clients/list-users appelé");

        $users = $repository->findBy(['role' => 'user']);

        // Debug: Vérifiez les résultats
        error_log("Nombre d'utilisateurs trouvés: " . count($users));

        return $this->json($users, Response::HTTP_OK, [], ['groups' => 'client:read']);
    }



}