<?php

namespace backend\models;

use Yii;
use yii\db\ActiveRecord;
use common\models\LoginHistory;

/**
 * This is the model class for table "users".
 *
 * @property int $id
 * @property string|null $name
 * @property string|null $email
 * @property string|null $phone_number
 * @property string|null $password_hash
 * @property string|null $two_fa_secret
 * @property string|null $created_at
 * @property string|null $updated_at
 * @property string|null $two_fa_method
 *
 * @property LoginHistory[] $loginhistories
 */
class User extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'users';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            ['id', 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['two_fa_method'], 'string'],
            [['name'], 'string', 'max' => 32],
            [['email'], 'string', 'max' => 256],
            [['phone_number'], 'string', 'max' => 15],
            [['password_hash'], 'string', 'max' => 60],
            [['two_fa_secret'], 'string', 'max' => 64],
            [['email'], 'unique'],
            [['phone_number'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'email' => 'Email',
            'phone_number' => 'Phone Number',
            'password_hash' => 'Password Hash',
            'two_fa_secret' => 'Two Fa Secret',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'two_fa_method' => 'Two Fa Method',
        ];
    }

    /**
     * Gets query for [[Loginhistories]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getLoginHistories()
    {
        return $this->hasMany(LoginHistory::class, ['user_id' => 'id']);
    }
}
