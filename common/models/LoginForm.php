<?php

namespace common\models;

use Yii;
use yii\base\Model;

/**
 * Login form
 */
class LoginForm extends Model
{
    public $email;
    public $password;

    private $_user;


    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            // username and password are both required
            [['email', 'password'], 'required'],
            // password is validated by validatePassword()
            ['email', 'validateEmail'],
            ['password', 'validatePassword'],
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
        if (!$this->hasErrors()) {
            $user = $this->getUser();
            if(!Yii::$app->security->validatePassword($this->password, $user->password_hash)) {
                $this->addError($attribute, 'Password is incorrect!');
            }
        }
    }

    /**
     * Validates the password.
     * This method serves as the inline validation for password.
     *
     * @param string $attribute the attribute currently being validated
     * @param array $params the additional name-value pairs given in the rule
     */
    public function validateEmail($attribute, $params)
    {
        if (!$this->hasErrors()) {
            $user = $this->getUser();
            if(empty($user)) {
                $this->addError($attribute,'User with this email not found');
            }
        }
        return isset($user) ? $user : null;
    }

    /**
     * Logs in a user using the provided username and password.
     *
     * @return User|false whether the user is logged in successfully
     */
    public function login()
    {
        if($this->validate()) {
            return $this->_user;
        }
        return false;
    }

    /**
     * Finds user by [[username]]
     *
     * @return User|null
     */
    protected function getUser()
    {
        if ($this->_user === null) {
            $this->_user = User::find()->where(['email'=> $this->email])->one();
        }

        return $this->_user;
    }
}
