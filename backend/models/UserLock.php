<?php
namespace backend\models;
/**
 * This is the model class for table "userlock".
 *
 * @property int $id
 * @property int $user_id
 * @property bool $locked
 * @property string|null $locked_at
 * @property string|null $locked_reason
 * @property string|null $unlock_secret
 * @property string|null $unlock_at
 *
 * @property User $user
 */
class UserLock extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'userlocks';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id'], 'required'],
            [['user_id'], 'default', 'value' => null],
            [['user_id'], 'integer'],
            [['locked'], 'boolean'],
            [['locked_at', 'unlock_at'], 'safe'],
            [['locked_reason'], 'string'],
            [['unlock_secret'], 'string', 'max' => 255],
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
            'locked' => 'Locked',
            'locked_at' => 'Locked At',
            'locked_reason' => 'Locked Reason',
            'unlock_secret' => 'Unlock Secret',
            'unlock_at' => 'Unlock At',
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