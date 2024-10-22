<?php

namespace common\models;

use Yii;
use yii\base\Model;

/**
 * Login form
 */
class RegisterForm extends Model
{
    public $username;
    public $email;
    public $password;
    public $phone_number;

    private $_user;


    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            // username and password are both required
            [['username', 'email', 'password', 'phone_number'], 'required'],
            // password is validated by validatePassword()
            ['password', 'validatePassword'],
            ['email', 'validateEmail'],
        ];
    }

    /**
     * Validates the password.
     * This method serves as the inline validation for password.
     *
     * @param string $attribute the attribute currently being validated
     * @param array $params the additional name-value pairs given in the rule
     */
    public function validatePassword($attribute, $params)
    {
        if($this->hasErrors()) {
            // Check if password is less than 8 characters
            if (strlen($this->$attribute) < 8) {
                $this->addError($attribute, 'Password must be at least 8 characters long.');
            }
        }
    }

    /**
     * Validates the email.
     * This method serves as the inline validation for email.
     *
     * @param string $attribute the attribute currently being validated
     * @param array $params the additional name-value pairs given in the rule
     */
    public function validateEmail($attribute, $params)
    {
        if(!$this->hasErrors()) {
            try {
                $user = User::find()->where(['email'=> $this->$attribute])->one();
                if(!empty($user)) {
                    $this->addError($attribute,'User with this email already exists.');
                }
            } catch (\Exception $e) {
                $this->addError($attribute, $e->getMessage());
            }
        }
    }

    /**
     * Return a new user.
     *
     * @return User
     */
    public function getUser()
    {
        $user = new User();
        $user->name = $this->username;
        $user->email = $this->email;
        $user->password_hash = Yii::$app->security->generatePasswordHash($this->password);
        $user->two_fa_secret = Yii::$app->tfa->createSecret();
        $user->phone_number = $this->phone_number;

        return $user;
    }
}
