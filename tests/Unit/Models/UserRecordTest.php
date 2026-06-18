<?php

declare(strict_types=1);

namespace app\tests\Unit\Models;

use app\models\UserRecord;
use app\tests\Support\UnitTester;

final class UserRecordTest extends \Codeception\Test\Unit
{
    protected UnitTester $tester;

    protected function _before(): void
    {
        UserRecord::deleteAll();
    }

    public function testSaveValidUser(): void
    {
        $model = new UserRecord([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ]);

        verify($model->save())->true();
        verify($model->id)->notEmpty();

        $I = $this->tester;
        $I->seeRecord(UserRecord::class, [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ]);
    }

    public function testRequiredFields(): void
    {
        $model = new UserRecord();

        verify($model->validate())->false();
        verify($model->errors)->arrayHasKey('name');
        verify($model->errors)->arrayHasKey('email');
    }

    public function testEmailMustBeValid(): void
    {
        $model = new UserRecord([
            'name' => 'Jane Doe',
            'email' => 'invalid-email',
        ]);

        verify($model->validate())->false();
        verify($model->errors)->arrayHasKey('email');
    }

    public function testEmailMustBeUnique(): void
    {
        $existing = new UserRecord([
            'name' => 'Existing User',
            'email' => 'jane@example.com',
        ]);
        verify($existing->save())->true();

        $model = new UserRecord([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ]);

        verify($model->validate())->false();
        verify($model->errors)->arrayHasKey('email');
    }
}
