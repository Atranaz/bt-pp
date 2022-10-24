<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Contracts\HttpClient\HttpClientInterface;

use App\Entity\PhoneReviews;
use App\Entity\User;
use App\Repository\PhoneReviewsRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Doctrine\ORM\EntityManagerInterface;

use Symfony\Component\Form\Forms;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

use Symfony\Component\Security\Core\Security;

class PhoneController extends AbstractController
{
    private $client;
    private $security;
    private PhoneReviewsRepository $PhoneReviewsRepository;
    public function __construct(
        HttpClientInterface $client,
        EntityManagerInterface $entityManager,
        Security $security
    ) {
        $this->client = $client;
        $this->security = $security;
        $this->PhoneReviewsRepository = $entityManager->getRepository(
            PhoneReviews::class
        );
    }

    #[Route('/phonelist', name: 'phone_list')]
    public function phoneGrid(): Response
    {
        $api_data = $this->phoneList();
        return $this->render('phone/grid.html.twig', ['data' => $api_data]);
    }

    #[Route('/phone/{slug}', name: 'phone_detail' , methods: ['GET','HEAD'])]
    public function show(string $slug): Response
    {
        $api_data = $this->phoneDetail($slug);
        $review_data = $this->getPhoneReviews($slug);
        $form = $this->createFormBuilder(null, [
            'action' => '/review',
            'method' => 'POST',
        ])
            ->add('phone_id', HiddenType::class, [
                'data' => $slug,
            ])
            ->add('review_text', TextareaType::class, [
                'label' => ' ',
                'attr' => [
                    'class' => 'form-control',
                    'id' => '1',
                ],
            ])
            ->getForm();

        return $this->render('phone/detailpage.html.twig', [
            'data' => $api_data,
            'reviews' => $review_data,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/review', name: 'create_review' , methods: ['POST','HEAD'])]
    public function createReview(
        Request $request,
        ManagerRegistry $doctrine
    ): Response {
        $user = $this->security->getUser();

        print_r($user);
        $form = $this->createFormBuilder()
            ->add('phone_id', HiddenType::class, [
                'data' => '',
            ])
            ->add('review_text', TextareaType::class, [
                'attr' => [
                    'class' => 'text-editor form-control',
                    'id' => '1',
                    'label' => ' ',
                ],
            ])

            ->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
        }
        $entityManager = $doctrine->getManager();
        $date = new \DateTime();
        $review = new PhoneReviews();
        $review->setPhoneId($data['phone_id']);
        $review->setUserId($user->getId());
        $review->setReviewText($data['review_text']);
        $review->setCreated($date);
        $review->setRating(4);

        $entityManager->persist($review);
        $entityManager->flush();

        $route = $request->headers->get('referer');

        return $this->redirect($route);
    }

    private function phoneList()
    {
        $response = $this->client->request(
            'GET',
            'http://gsmarena-api.herokuapp.com/top',
            [
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ]
        );
        $statusCode = $response->getStatusCode();
        if ($statusCode === 200) {
            $content = $response->toArray();
            $content = $content[0]['list'];
        } else {
            $content = null;
        }

        return $content;
    }

    private function phoneDetail($url)
    {
        $response = $this->client->request(
            'GET',
            'http://gsmarena-api.herokuapp.com/device/' . $url,
            [
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ]
        );
        $statusCode = $response->getStatusCode();
        if ($statusCode === 200) {
            $content = $response->toArray();
        } else {
            $content = null;
        }

        return $content;
    }

    private function getPhoneReviews(string $phone_id)
    {
        $review_array = [];
        $reviews = $this->PhoneReviewsRepository->findBy([
            'phone_id' => $phone_id,
        ]);

        if (!$reviews) {
            $review_array = [];
        }

        foreach ($reviews as $key => $value) {
            $review_arr['phone_review'] = $value->getReviewText();
            $review_arr['rating'] = $value->getRating();
            $review_arr['email'] = $this->getUserEmail($value->getUserId());
            $review_arr['date'] = 'Sat, 24 Nov 2022';
            $review_array[$key] = $review_arr;
            $review_arr = [];
        }

        return $review_array;
    }

    private function getUserEmail($user_id)
    {
        $userRepository = $this->getDoctrine()->getRepository(User::class);

        $user = $userRepository->findOneBy([
            'id' => $user_id,
        ]);
        return $user->getEmail();
    }
}
