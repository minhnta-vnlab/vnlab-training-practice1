<?php
/** @var yii\web\View $this */
/** @var yii\bootstrap5\ActiveForm $form */
/** @var string $method */
/** @var string $email */
/** @var frontend\models\TwoFAForm $model */

use yii\bootstrap5\Html;
use yii\bootstrap5\ActiveForm;

$this->title = 'Login Verification';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="site-login-verification">
    <h1><?= Html::encode($this->title) ?></h1>
    <h4>Please fill in the verification code</h4>
    <p>
        <?php
            if ($method == 'email') {
                echo "We have sent a verification code to <a href='mailto:$email'>$email</a>. Please open your email box to see it.";
            } else {
                echo "Please open your Authenticator to get verification code.";
            }
        ?>
        <?php $form = ActiveForm::begin(['id' => 'login-verify-form']); ?>
            <?= $form->field($model, "code")->textInput(['autofocus' => true]) ?>
            <div class="form-group">
                <?= Html::submitButton('Submit', ['class' => 'btn btn-primary', 'name' => 'login-button']) ?>
            </div>
        <?php ActiveForm::end(); ?>
    </p>
</div>