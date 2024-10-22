<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "loginverifications".
 *
 * @property int $id
 * @property int|null $user_id
 * @property string|null $code
 * @property string|null $verification_method
 * @property string|null $issued_at
 * @property string|null $expired_at
 *
 * @property User $user
 */
class LoginVerification extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'loginverifications';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id'], 'default', 'value' => null],
            [['user_id'], 'integer'],
            [['verification_method'], 'string'],
            [['issued_at', 'expired_at'], 'safe'],
            [['code'], 'string', 'max' => 8],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'code' => 'Code',
            'verification_method' => 'Verification Method',
            'issued_at' => 'Issued At',
            'expired_at' => 'Expired At',
        ];
    }

    /**
     * Gets query for [[User]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }
}
