<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AuthControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em     = static::getContainer()->get(EntityManagerInterface::class);
    }

    // --- register ---

    public function testRegisterCreatesUser(): void
    {
        $this->post('/auth/register', ['email' => 'new@example.com', 'password' => 'password123']);

        $this->assertResponseStatusCodeSame(201);
        $body = $this->responseBody();
        $this->assertArrayHasKey('userId', $body['data']);
        $this->assertSame('new@example.com', $body['data']['email']);
    }

    public function testRegisterRejectsDuplicateEmail(): void
    {
        $this->seedUser('dupe@example.com', 'password123');

        $this->post('/auth/register', ['email' => 'dupe@example.com', 'password' => 'password123']);

        $this->assertResponseStatusCodeSame(409);
    }

    public function testRegisterValidatesEmail(): void
    {
        $this->post('/auth/register', ['email' => 'not-an-email', 'password' => 'password123']);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testRegisterValidatesPasswordLength(): void
    {
        $this->post('/auth/register', ['email' => 'x@example.com', 'password' => 'short']);

        $this->assertResponseStatusCodeSame(422);
    }

    // --- login ---

    public function testLoginReturnsTokens(): void
    {
        $this->seedUser('login@example.com', 'password123');

        $this->post('/auth/login', ['email' => 'login@example.com', 'password' => 'password123']);

        $this->assertResponseIsSuccessful();
        $body = $this->responseBody();
        $this->assertArrayHasKey('accessToken', $body['data']);
        $this->assertArrayHasKey('refreshToken', $body['data']);
        $this->assertSame(900, $body['data']['expiresIn']);
    }

    public function testLoginRejectsWrongPassword(): void
    {
        $this->seedUser('login2@example.com', 'correctpass');

        $this->post('/auth/login', ['email' => 'login2@example.com', 'password' => 'wrongpass']);

        $this->assertResponseStatusCodeSame(401);
    }

    public function testLoginRejectsUnknownEmail(): void
    {
        $this->post('/auth/login', ['email' => 'nobody@example.com', 'password' => 'password123']);

        $this->assertResponseStatusCodeSame(401);
    }

    // --- refresh ---

    public function testRefreshRotatesToken(): void
    {
        $this->seedUser('refresh@example.com', 'password123');
        $this->post('/auth/login', ['email' => 'refresh@example.com', 'password' => 'password123']);
        $firstRefresh = $this->responseBody()['data']['refreshToken'];

        $this->post('/auth/refresh', ['refreshToken' => $firstRefresh]);

        $this->assertResponseIsSuccessful();
        $body = $this->responseBody();
        $this->assertArrayHasKey('accessToken', $body['data']);
        $this->assertNotSame($firstRefresh, $body['data']['refreshToken']);
    }

    public function testRefreshRejectsReusedToken(): void
    {
        $this->seedUser('refresh2@example.com', 'password123');
        $this->post('/auth/login', ['email' => 'refresh2@example.com', 'password' => 'password123']);
        $refreshToken = $this->responseBody()['data']['refreshToken'];

        $this->post('/auth/refresh', ['refreshToken' => $refreshToken]);
        $this->post('/auth/refresh', ['refreshToken' => $refreshToken]); // reuse

        $this->assertResponseStatusCodeSame(401);
    }

    // --- logout ---

    public function testLogoutSucceeds(): void
    {
        $this->seedUser('logout@example.com', 'password123');
        $this->post('/auth/login', ['email' => 'logout@example.com', 'password' => 'password123']);
        $accessToken = $this->responseBody()['data']['accessToken'];

        $this->post('/auth/logout', [], ['HTTP_AUTHORIZATION' => 'Bearer ' . $accessToken]);

        $this->assertResponseStatusCodeSame(204);
    }

    // --- validate ---

    public function testValidateAcceptsValidToken(): void
    {
        $this->seedUser('validate@example.com', 'password123');
        $this->post('/auth/login', ['email' => 'validate@example.com', 'password' => 'password123']);
        $accessToken = $this->responseBody()['data']['accessToken'];

        $this->client->request('GET', '/auth/validate', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $accessToken,
        ]);

        $this->assertResponseIsSuccessful();
        $body = $this->responseBody();
        $this->assertSame('validate@example.com', $body['data']['email']);
    }

    // --- Helpers ---

    private function seedUser(string $email, string $plainPassword): User
    {
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $user   = new User($email, '');
        $hash   = $hasher->hashPassword($user, $plainPassword);
        $user->setPasswordHash($hash);
        $this->em->persist($user);
        $this->em->flush();
        return $user;
    }

    private function post(string $url, array $body, array $server = []): void
    {
        $this->client->request(
            'POST',
            $url,
            [],
            [],
            array_merge(['CONTENT_TYPE' => 'application/json'], $server),
            json_encode($body),
        );
    }

    private function responseBody(): array
    {
        return json_decode($this->client->getResponse()->getContent(), true);
    }
}