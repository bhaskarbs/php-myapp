<?php

declare(strict_types=1);

namespace app\tests\Functional;

use app\models\UserRecord;
use app\tests\Support\FunctionalTester;

final class UserApiCest
{
    public function _before(FunctionalTester $I): void
    {
        UserRecord::deleteAll();
    }

    public function createUserSuccessfully(FunctionalTester $I): void
    {
        $this->postCreateUser($I, [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $response = $this->decodeResponse($I);

        verify($response['success'])->true();
        verify($response['data']['name'])->equals('John Doe');
        verify($response['data']['email'])->equals('john@example.com');
        verify($response['data']['id'])->notEmpty();

        $I->seeRecord(UserRecord::class, [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);
    }

    public function createUserWithMissingFields(FunctionalTester $I): void
    {
        $this->postCreateUser($I, [
            'name' => 'John Doe',
        ]);

        $response = $this->decodeResponse($I);

        verify($response['success'])->false();
        verify($response['errors'])->arrayHasKey('email');
        $I->dontSeeRecord(UserRecord::class, ['name' => 'John Doe']);
    }

    public function createUserWithInvalidEmail(FunctionalTester $I): void
    {
        $this->postCreateUser($I, [
            'name' => 'John Doe',
            'email' => 'not-an-email',
        ]);

        $response = $this->decodeResponse($I);

        verify($response['success'])->false();
        verify($response['errors'])->arrayHasKey('email');
        $I->dontSeeRecord(UserRecord::class, ['name' => 'John Doe']);
    }

    public function createUserWithDuplicateEmail(FunctionalTester $I): void
    {
        $user = new UserRecord([
            'name' => 'Existing User',
            'email' => 'john@example.com',
        ]);
        verify($user->save())->true();

        $this->postCreateUser($I, [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $response = $this->decodeResponse($I);

        verify($response['success'])->false();
        verify($response['errors'])->arrayHasKey('email');
        verify(UserRecord::find()->count())->equals(1);
    }

    /**
     * @param array<string, string> $params
     */
    private function postCreateUser(FunctionalTester $I, array $params): void
    {
        $I->sendAjaxPostRequest('/index-test.php?r=user/create', $params);
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeResponse(FunctionalTester $I): array
    {
        $response = json_decode($I->grabPageSource(), true);

        verify($response)->isArray();

        return $response;
    }
}
