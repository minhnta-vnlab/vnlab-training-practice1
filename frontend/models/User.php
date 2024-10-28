<?php

namespace frontend\models;

use Yii;
use yii\base\Model;
use yii\web\IdentityInterface;
use yii\base\NotSupportedException;

/**
 * This is the model class for table "users".
 *
 * @property int $id
 * @property string|null $name
 * @property string|null $email
 * @property string|null $phone_number
 * @property string|null $auth_key
 * @property string|null $two_fa_method
 *
//  * @property LoginHistory[] $loginhistories
 */
class User extends Model implements IdentityInterface
{
   public int $id = -1;
   public string|null $name;
   public string|null $email;
   public string|null $phone_number;
   public string|null $two_fa_secret;
   public string|null $two_fa_method;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            ['id', 'integer'],
            [['two_fa_method'], 'string'],
            [['name'], 'string', 'max' => 32],
            [['email'], 'string', 'max' => 256],
            [['phone_number'], 'string', 'max' => 15],
            [['email'], 'unique'],
            [['phone_number'], 'unique'],
            [['two_fa_secret'], 'string', 'max' => 32]
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
            'two_fa_method' => 'Two Fa Method',
            'two_fa_secret' => 'Two Fa Secret'
        ];
    }
    public static function findIdentity($id)
    {
        $response = Yii::$app->httpClient->get("users/$id")->send();
        if ($response->getIsOk()) {
            $user = new User();
            $user->attributes = $response->data;
            return $user;            
        }
        return null;
    }

    public static function findIdentityByAccessToken($token, $type = null)
    {
        throw new NotSupportedException('"findIdentityByAccessToken" is not implemented.');
    }

    // public static function findByUsername($username)
    // {
    //     return static::findOne(['name' => $username]);
    // }

    // public function validatePassword($password)
    // {
    //     return Yii::$app->security->validatePassword($password, $this->password_hash);
    // }

    public function getId()
    {
        return $this->id; // Assuming you have an 'id' attribute
    }


    public function getAuthKey()
    {
        return $this->two_fa_secret;
    }

    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }
}
