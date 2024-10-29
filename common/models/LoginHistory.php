<?php

namespace common\models;

use Yii;
use backend\models\User;

/**
 * This is the model class for table "loginhistories".
 *
 * @property int $id
 * @property int|null $user_id
 * @property string|null $ip
 * @property string|null $login_time
 * @property string|null $ua
 * @property string|null $message
 *
 * @property User $user
 */
class LoginHistory extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'loginhistories';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            ['id', 'integer'],
            [['user_id'], 'default', 'value' => null],
            [['user_id'], 'integer'],
            [['ip'], 'string'],
            [['login_time'], 'safe'],
            [['ua'], 'string', 'max' => 512],
            [['message'], 'string', 'max' => 32],
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
            'ip' => 'Ip',
            'login_time' => 'Login Time',
            'ua' => 'Ua',
            'message' => 'Message',
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

    public function isFailed() {
        return $this->message != "login_success";
    }
}
