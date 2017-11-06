<?php
namespace frontend\controllers;

use dektrium\user\controllers\SecurityController;
use dektrium\user\events\ConnectEvent;
use Yii;
use yii\base\InvalidParamException;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use common\models\LoginForm;
use frontend\models\PasswordResetRequestForm;
use frontend\models\ResetPasswordForm;
use frontend\models\SignupForm;
use frontend\models\ContactForm;
use common\models\User;

/**
 * Site controller
 */
class SiteController extends Controller
{
    public $successUrl = 'Success';
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['logout', 'signup'],
                'rules' => [
                    [
                        'actions' => ['signup'],
                        'allow' => true,
                        'roles' => ['?'],
                    ],
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
            'auth' => [
                'class' => 'yii\authclient\AuthAction',
                'successCallback' => [$this, 'successCallback']
            ]
        ];
    }

    public function successCallback($client)
    {
        $attributes = $client->getUserAttributes();

        $user = \backend\models\User::find()->where(['email' => $attributes['email']])->one();
        if(!empty($user)){

            if($user->active == 'inactive')
            {
                Yii::$app->session->setFlash('error', 'Ваш аккаунт заблокирован.');
                return $this->goHome();

            }else{
                Yii::$app->user->login($user);
            }


        }else{
            $User = new User();
            $User->username = $attributes['email'];
            $User->email = $attributes['email'];
            $User->setPassword(rand(5,1000));
            $User->generateAuthKey();
            $User->save();
            Yii::$app->user->login($User);
        }
    }

    /**
     * Displays homepage.
     *
     * @return mixed
     */
    public function actionIndex()
    {
        return $this->render('index');
    }

    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            $user = \backend\models\User::findOne(Yii::$app->user->id);
            if($user->active == 'inactive')
            {

                Yii::$app->user->logout();
                Yii::$app->session->setFlash('error', 'Ваш аккаунт заблокирован.');
                return $this->goHome();

            }
            else{
                return $this->goBack();
            }

        } else {
            return $this->render('login', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Logs out the current user.
     *
     * @return mixed
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }

    /**
     * Displays contact page.
     *
     * @return mixed
     */
    public function actionContact()
    {
        $model = new ContactForm();
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->sendEmail(Yii::$app->params['adminEmail'])) {
                Yii::$app->session->setFlash('success', 'Thank you for contacting us. We will respond to you as soon as possible.');
            } else {
                Yii::$app->session->setFlash('error', 'There was an error sending your message.');
            }

            return $this->refresh();
        } else {
            return $this->render('contact', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Displays about page.
     *
     * @return mixed
     */
    public function actionAbout()
    {
        return $this->render('about');
    }

    /**
     * Signs user up.
     *
     * @return mixed
     */
    public function actionSignup()
    {
        $model = new SignupForm();
        if ($model->load(Yii::$app->request->post())) {
            if ($user = $model->signup()) {
                $email = \Yii::$app->mailer->compose()
                    ->setTo($user->email)
                    ->setFrom([\Yii::$app->params['supportEmail'] => \Yii::$app->name . ' robot'])
                    ->setSubject('Signup Confirmation')  
                    ->setHtmlBody(" Click this link <a href=http://test.msoft.kz/frontend/web/site/confirm?id=".$user->id."&key=".$user->auth_key."'>confirm</a> registration"
                    )
                    ->send();
                if($email){
                    Yii::$app->getSession()->setFlash('success',Yii::t('app','Check your email! Perhaps a letter to the "spam" folder!') );
                }
                else{
                    Yii::$app->getSession()->setFlash('warning','Failed, contact Admin!');
                }
                return $this->goHome();
            }
        }

        return $this->render('signup', [
            'model' => $model,
        ]);
    }

    public function actionConfirm($id, $key)
    {
        $user = \common\models\User::find()->where([
            'id'=>$id,
            'auth_key'=>$key,
            'status'=>0,
        ])->one();
        if(!empty($user)){
            $user->status=10;
            $user->save();
            Yii::$app->getSession()->setFlash('success','Регистрация подтверждена!');
        }
        else{
            Yii::$app->getSession()->setFlash('warning','Failed!');
        }
        return $this->goHome();
    }

    /**
     * Requests password reset.
     *
     * @return mixed
     */
    public function actionRequestPasswordReset()
    {
        $model = new PasswordResetRequestForm();
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->sendEmail()) {
                Yii::$app->session->setFlash('success', 'Check your email for further instructions.');

                return $this->goHome();
            } else {
                Yii::$app->session->setFlash('error', 'Sorry, we are unable to reset password for the provided email address.');
            }
        }

        return $this->render('requestPasswordResetToken', [
            'model' => $model,
        ]);
    }

    /**
     * Resets password.
     *
     * @param string $token
     * @return mixed
     * @throws BadRequestHttpException
     */
    public function actionResetPassword($token)
    {
        try {
            $model = new ResetPasswordForm($token);
        } catch (InvalidParamException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }

        if ($model->load(Yii::$app->request->post()) && $model->validate() && $model->resetPassword()) {
            Yii::$app->session->setFlash('success', 'New password saved.');

            return $this->goHome();
        }

        return $this->render('resetPassword', [
            'model' => $model,
        ]);
    }
}
