<?php

/** @var yii\web\View $this */
/** @var yii\bootstrap5\ActiveForm $form */
/** @var \common\models\UnlockUserForm $model */

use yii\bootstrap5\Html;
use yii\bootstrap5\ActiveForm;

$this->title = 'Unlock';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="site-unlock">
    <h1><?= Html::encode($this->title) ?></h1>

    <p>Please enter your password to unlock your account:</p>

    <div class="row">
        <div class="col-lg-5">
            <?php $form = ActiveForm::begin(['id' => 'unlock-form']); ?>

                <?= $form->field($model, 'unlock_secret')->hiddenInput()->label(false) ?>

                <?= $form->field($model, 'password')->passwordInput(['autofocus' => true]) ?>

                <div class="form-group">
                    <?= Html::submitButton('Unlock', ['class' => 'btn btn-primary', 'name' => 'unlock-button']) ?>
                </div>
            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>
