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

class PhoneController extends AbstractController
{
    private $client;
    private PhoneReviewsRepository $PhoneReviewsRepository;
    public function __construct(
        HttpClientInterface $client,
        EntityManagerInterface $entityManager
    ) {
        $this->client = $client;
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
        $review_data = $this->getPhoneReviewsCOPY($slug);
        return $this->render('phone/detailpage.html.twig', [
            'data' => $api_data,
            'reviews' => $review_data,
        ]);
    }

    #[Route('/review', name:'create_review')]
    public function createReview(ManagerRegistry $doctrine): Response
    {
        // make sure the data is correct then start process
        // IF data not suitable return back with error

        $entityManager = $doctrine->getManager();
        $review = new PhoneReviews();
        $review->setPhoneId('google_pixel_7_pro-11908');
        $review->setUserId(1);
        $review->setReviewText('great Phone');
        $review->setRating(4);

        $entityManager->persist($review);

        $entityManager->flush();

        return new Response('Saved new entery');
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

    private function getPhoneReviewsCOPY(string $phone_id)
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
