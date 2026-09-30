<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Entity\Product;
use App\Service\PromotionService;
use PHPUnit\Framework\TestCase;

class PromotionServiceTest extends TestCase
{
    private PromotionService $service;

    protected function setUp(): void
    {
        $this->service = new PromotionService();
    }

    public function testReturnsNormalPriceWhenNoPromotion(): void
    {
        $product = $this->createProduct(50.00);
        $product->setPromoPrice(30);

        $this->assertSame(50.00, $this->service->getCurrentPrice($product));
        $this->assertFalse($this->service->isOnPromotion($product));
    }

    public function testReturnsPromoPriceDuringPeriod(): void
    {
        $product = $this->createProduct(50.0);

        $product->setPromoPrice(40.0);
        $product->setPromoStartsAt($this->retirer1heure($this->dateNow()));
        $product->setPromoEndsAt($this->ajouter1heure($this->dateNow()));

        $this->assertSame(40.0, $this->service->getCurrentPrice($product));
        $this->assertTrue($this->service->isOnPromotion($product));
    }

    public function testReturnsNormalPriceBeforePromotionPeriod(): void
    {
        $product = $this->createProduct(50.00);
        $product->setPromoPrice(40.0);

        $product->setPromoStartsAt($this->ajouter1heure($this->dateNow()));
        $product->setPromoEndsAt($this->ajouter1heure($this->ajouter1heure($this->dateNow())));

        $this->assertSame(50.0, $this->service->getCurrentPrice($product));
        $this->assertFalse($this->service->isOnPromotion($product));
    }

    public function testReturnsNormalPriceAfterPromotionPeriod(): void
    {
        $product = $this->createProduct(50.00);

        $product->setPromoPrice(40.0);
        $product->setPromoStartsAt($this->retirer1heure($this->dateNow()));
        $product->setPromoEndsAt($this->retirer1heure($this->retirer1heure($this->dateNow())));

        $this->assertSame(50.0, $this->service->getCurrentPrice($product));
        $this->assertFalse($this->service->isOnPromotion($product));
    }

    public function testPromoPriceEqualToNormalIsNotActive()
    {
        $product = $this->createProduct(50.0);

        $product->setPromoPrice(50.0);
        $product->setPromoStartsAt($this->retirer1heure($this->dateNow()));
        $product->setPromoEndsAt($this->ajouter1heure($this->dateNow()));

        $this->assertSame(50.0, $this->service->getCurrentPrice($product));
        $this->assertFalse($this->service->isOnPromotion($product));
    }

    public function testPromoPriceGreaterThanNormalIsNotActive(): void
    {
        $product = $this->createProduct(50.0);

        $product->setPromoPrice(70.0);
        $product->setPromoStartsAt($this->retirer1heure($this->dateNow()));
        $product->setPromoEndsAt($this->ajouter1heure($this->dateNow()));

        $this->assertSame(50.0, $this->service->getCurrentPrice($product));
        $this->assertFalse($this->service->isOnPromotion($product));
    }

    public function testInvertedDatesAreNotActive(): void
    {
        $product = $this->createProduct(50.0);
        $product->setPromoPrice(40.0);

        $product->setPromoEndsAt($this->retirer1heure($this->dateNow()));
        $product->setPromoStartsAt($this->ajouter1heure($this->dateNow()));

        $this->assertSame(50.0, $this->service->getCurrentPrice($product));
        $this->assertFalse($this->service->isOnPromotion($product));
    }

    public function testBoundaryStartIsIncluded(): void
    {
        $product = $this->createProduct(50.0);
        $product->setPromoPrice(40.0);
        $now = $this->dateNow();
        $product->setPromoStartsAt($now);
        $product->setPromoEndsAt($this->ajouter1heure($this->dateNow()));

        $this->assertSame(40.0, $this->service->getCurrentPrice($product, $now));
        $this->assertTrue($this->service->isOnPromotion($product, $now));
    }

    public function testBoundaryEndIsIncluded(): void
    {
        $product = $this->createProduct(50.0);
        $product->setPromoPrice(40.0);
        $now = $this->dateNow();
        $product->setPromoEndsAt($now);
        $product->setPromoStartsAt($this->retirer1heure($this->dateNow()));

        $this->assertSame(40.0, $this->service->getCurrentPrice($product, $now));
        $this->assertTrue($this->service->isOnPromotion($product, $now));
    }

    private function createProduct(float $price, ?float $promoPrice = null): Product
    {
        $product = new Product();
        $product->setName('Test Product')->setPrice($price);

        //        if (null !== $promoPrice) {
        //            $product->setPromoPrice($promoPrice);
        //            $product->setPromoStartsAt(new \DateTimeImmutable('2026-08-01 00:00:00'));
        //            $product->setPromoEndsAt(new \DateTimeImmutable('2026-08-31 23:59:59'));
        //        }

        return $product;
    }

    private function dateNow(): \DateTimeImmutable
    {
        return new \DateTimeImmutable();
    }

    private function ajouter1heure(\DateTimeImmutable $date): \DateTimeImmutable
    {
        return $date->add(new \DateInterval('PT1H'));
    }

    private function retirer1heure(\DateTimeImmutable $date): \DateTimeImmutable
    {
        return $date->sub(new \DateInterval('PT1H'));
    }
}
