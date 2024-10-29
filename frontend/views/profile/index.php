<?php


/** @var yii\web\View $this */
/** @var yii\bootstrap5\ActiveForm $form */
/** @var frontend\models\User $user */
/** @var common\models\Update2FAForm $model */
/** @var yii\data\ArrayDataProvider $dataProvider */

use frontend\consts\CacheKey;
use frontend\consts\TagKey;
use yii\bootstrap5\Html;
use yii\bootstrap5\ActiveForm;
use yii\caching\TagDependency;
use yii\grid\GridView;
if($this->beginCache(CacheKey::PROFILE_PAGE_INDEX->name)) {
Yii::debug("Renew?");
$this->title = 'Profile';
?>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const dropdown = document.getElementById('update2faform-two_fa_method');
        const submitButton = document.getElementById('submit-button');
        const options = {
            email: 'tfa-email',
            google_authenticator: 'tfa-authenticator',
        };
    
        function updateVisibility() {
            const selectedValue = dropdown.value;
            const submitButton = document.getElementById('submit-button');
    
            // Hide all elements first
            Object.values(options).forEach((elementId) => {
                document.getElementById(elementId).hidden = true;
            });
    
            // Show the selected element
            if (options[selectedValue]) {
                console.log('Selecting: ', options[selectedValue])
                document.getElementById(options[selectedValue]).hidden = (false || submitButton.disabled);
            }
        }
        
        // Store the initial value of the dropdown
        const initialValue = dropdown.value;

        updateVisibility();

        // Event listener for changes in the dropdown
        dropdown.addEventListener('change', function () {
            // Enable or disable the submit button based on whether the value has changed
            submitButton.disabled = (this.value === initialValue);
            updateVisibility();
        });
    });
</script>
<div class="site-profile">
    <h1>Welcome <?= $user->name ?></h1>
    <div>
        <?php $form = ActiveForm::begin(['id' => 'profile-form', 'class' => 'd-flex flex-column p-5 gap-3']); ?>
            <?= $form
            ->field($model, "user_id")
            ->hiddenInput()->label(false)
            ?>
            <?= $form
            ->field($model, "two_fa_method")
            ->label("Two-Factor Authentication Method")
            ->dropDownList([
                '' => 'None',
                'email' => 'Email',
                'google_authenticator' => 'Authenticator'
            ]) ?>
            <div id="tfa-authenticator" class="border rounded flex-column justify-content-between p-4 shadow-sm" hidden>
                <h4 class="mb-3">Two-Factor Authentication Setup</h4>
                <p class="text-muted">
                    You are currently using <span class="fw-bold">Authenticator</span> as your two-factor authentication (2FA) method.<br>
                    To bolster your account security, please open your <span class="fw-bold">Authenticator application</span> and <span class="fw-bold">scan</span> the QR code below to link your Authenticator. 
                    Each authentication will require a code from this application.
                </p>
                <div class="text-center">
                    <img src="/api/user/two-factor-qr?id=<?= $user->id ?>" width="300" alt="QR Code" class="img-fluid">
                    <p class="mt-2 text-muted">Please ensure the QR code is scanned correctly to complete the setup.</p>
                </div>
                <?= $form
                ->field($model, "code")
                ->label("Please fill in the code received in your Authenticator app to complete the setup:")
                ->textInput(['placeholder' => 'Code', 'autofocus' => true, 'maxlength' => 6,]) 
                ?>
            </div>
            <div id="tfa-email" class="border rounded flex-column justify-content-between p-4 shadow-sm" hidden>
                <h4 class="mb-3">Two-Factor Authentication Setup</h4>
                <p class="text-muted">
                    You are currently using <span class="fw-bold">Email</span> as your two-factor authentication (2FA) method.<br>
                    To ensure the security of your account, a verification code will be sent to your registered email address <span class="fw-bold"><?=$user->email ?></span> each time you log in.
                </p>
                <p class="text-muted">
                    Please check your inbox for the verification code whenever prompted. If you do not receive an email, please check your spam or junk folder.
                </p>
                <p class="text-muted">
                    If you wish to change your 2FA method, you can do so in your account settings.
                </p>
            </div>
            <div class="form-group">
                <?= Html::submitButton('Change', ['class' => 'btn btn-primary', 'name' => 'login-button', 'id' => 'submit-button', 'disabled' => true]) ?>
            </div>
        <?php ActiveForm::end(); ?>
        <h3>Recent login</h3>
<?php
    $this->endCache();
}
if($this->beginCache(CacheKey::PROFILE_PAGE_LOGIN_HISTORIES->name, [
    'dependency' => new TagDependency(['tags' => TagKey::USER_LOGIN_HISTORIES->name])
])) {
    Yii::debug("Renew");
?>
        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            'columns' => [
                ['class' => 'yii\grid\SerialColumn'],
                // 'id',
                'login_time',
                'ip',
                'ua',
                'message',
                // [
                //     'class' => ActionColumn::className(),
                //     'urlCreator' => function ($action, LoginHistory $model, $key, $index, $column) {
                //         return Url::toRoute([$action, 'id' => $model->id]);
                //     }
                // ],
            ],
        ]); ?>
<?php
    $this->endCache();
}
?>
    </div>
</div>