<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Category;
use App\Entity\Product;

class CatalogTest extends FunctionalTestCase
{
    public function testCatalogPageDisplaysProducts(): void
    {
        $this->client->request('GET', '/products');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Catan');
    }

    public function testInactiveProductIsHidden(): void
    {
        $inactive = new Product();
        $inactive->setName('Produit Test');
        $inactive->setReference('TEST-999');
        $inactive->setPrice(10.00);
        $inactive->setStock(5);
        $inactive->setIsActive(false);
        $this->entityManager()->persist($inactive);
        $this->entityManager()->flush();

        $this->client->request('GET', '/products');

        $this->assertSelectorTextNotContains('body', 'Produit Test');
    }

    public function testMatureProductHiddenForMinor(): void
    {
        $this->login('minor@example.com');

        $product = $this->repository(Product::class)->findOneBy(['reference' => 'LIM-001']);
        $this->assertNotNull($product);

        $this->client->request('GET', '/products/'.$product->getId());

        $this->assertResponseStatusCodeSame(404);
    }

    public function testMatureProductVisibleForAdult(): void
    {
        $this->login('client@example.com');

        $product = $this->repository(Product::class)->findOneBy(['reference' => 'LIM-001']);
        $this->assertNotNull($product);

        $this->client->request('GET', '/products/'.$product->getId());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Limite Limite');
    }

    public function testFilterByCategory(): void
    {
        // ajout de la Categorie fictive a un produi fictif
        $cat = new Category();
        $cat->setName('CatTest');
        $cat->setSlug("cat-test");

        $product = new Product();
        $product->setName('Produit de test');
        $product->setReference('TEST-111');
        $product->setPrice(10.00);
        $product->setStock(5);
        $product->setIsActive(true);

        $product->addCategory($cat);
        $cat->addProduct($product);

        $this->entityManager()->persist($product);
        $this->entityManager()->persist($cat);
        $this->entityManager()->flush();

        $this->client->request('GET', '/categories/cat-test');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Produit de test');
    }
}
