<?php

namespace App\Tests;
use App\Entity\PhoneReviews;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class PhoneTest extends KernelTestCase
{
    protected $entityManager;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        DatabasePrimer::prime($kernel);

        $this->entityManager = $kernel
            ->getContainer()
            ->get('doctrine')
            ->getManager();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->entityManager->close();
        $this->entityManager = null;
    }
    /** @test */
    public function a_review_record_can_be_created_in_the_database()
    {
        // Set up

        // Stock
        $review = new PhoneReviews();
        $review->setPhoneId('google_pixel_7_pro-11908');
        $review->setUserId(1);
        $review->setReviewText('this is review text');

        $review->setRating(4);

        $this->entityManager->persist($review);

        // Do something
        $this->entityManager->flush();

        $reviewRepository = $this->entityManager->getRepository(
            PhoneReviews::class
        );

        $reviewRecord = $reviewRepository->findOneBy([
            'phone_id' => 'google_pixel_7_pro-11908',
        ]);

        // Make assertions
        $this->assertEquals(1, $stockRecord->getUserId());
        $this->assertEquals(
            'this is review text',
            $stockRecord->getReviewText()
        );

        $this->assertEquals('4', $stockRecord->getRating());
    }
}
