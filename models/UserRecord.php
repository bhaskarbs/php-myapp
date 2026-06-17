<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 */
class UserRecord extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%user}}';
    }

    public function rules(): array
    {
        return [
            [['name', 'email'], 'required'],
            [['name', 'email'], 'trim'],
            [['name', 'email'], 'string', 'max' => 255],
            ['email', 'email'],
            ['email', 'unique'],
        ];
    }
}
