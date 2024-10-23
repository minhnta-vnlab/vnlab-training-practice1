<?php
    namespace backend\controllers;
    use yii\rest\ActiveController;
    use common\models\User;
    use common\models\Update2FAForm;
    use Yii;
    use yii\web\Response;
    use Endroid\QrCode\QrCode;
    use Endroid\QrCode\Color\Color;
    use Endroid\QrCode\Encoding\Encoding;
    use Endroid\QrCode\ErrorCorrectionLevel;
    use Endroid\QrCode\RoundBlockSizeMode;
    use Endroid\QrCode\Writer\SvgWriter;

    class UserController extends ActiveController {
        public $modelClass = "common\models\User";

        public function actionTwoFactorQr() {
            $id = Yii::$app->request->get("id");
            $user = User::findOne($id);
            if (!$user) {
                Yii::$app->response->statusCode = 404;
                return [
                    'error' => 'Not found',
                    'message' => 'User with this id is not found'
                ];
            }

            $writer = new SvgWriter();

            $email = $user->email;
            $secret = $user->two_fa_secret;

            // Create QR code
            $qrCode = new QrCode(
                data: "otpauth://totp/VNLabTraining:$email?secret=$secret",
                encoding: new Encoding('UTF-8'),
                errorCorrectionLevel: ErrorCorrectionLevel::Low,
                size: 300,
                margin: 10,
                roundBlockSizeMode: RoundBlockSizeMode::Margin,
                foregroundColor: new Color(0, 0, 0),
                backgroundColor: new Color(255, 255, 255)
            );

            $result = $writer->write($qrCode);

            // Force the response to be treated as XML/SVG
            Yii::$app->response->format = Response::FORMAT_RAW;
            Yii::$app->response->headers->add('Content-Type', 'image/svg+xml');

            return $result->getString();
        }

        public function actionUpdateTwoFa() {
            $model = new Update2FAForm();
            $model->attributes = Yii::$app->request->bodyParams;
            if(!$model->validate()) {
                Yii::$app->response->statusCode = 400;
                return [
                    'error' => "Bad request",
                    'message' => $model->getFirstErrors(),
                ];
            }
            $user = User::findOne($model->user_id);
            if(!$user) {
                Yii::$app->response->statusCode = 400;
                return [
                    'error' => "Bad request",
                    'message' => 'User with this id is not found',
                ];
            }
            if($model->two_fa_method == 'google_authenticator') {
                /**
                 * @var \RobThree\Auth\TwoFactorAuth
                 */
                $tfa = Yii::$app->tfa;
                $result = $tfa->verifyCode($user->two_fa_secret, $model->code);
    
                if(!$result) {
                    Yii::$app->response->statusCode = 400;
                    return [
                        'error' => "Bad request",
                        'message' => 'Authenticator code is not correct',
                    ];
                }
            }
    
            $user->two_fa_method = $model->two_fa_method;
            if($user->save()) {
                return [
                    'message' => "Successfully updated user's 2FA method",
                    "data" => [
                        "user" => $user
                    ]
                ];
            }
            Yii::$app->response->statusCode = 500;
            return [
                'error' => "Internal Server Error",
                'message' => 'Something wrong happened when saving user',
            ];
        }
    }