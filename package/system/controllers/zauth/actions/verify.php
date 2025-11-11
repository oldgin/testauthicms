<?php

class actionZauthVerify extends cmsAction {

    public function run($pass_token = null) {

        if (empty($this->options['verify'])) {
            cmsCore::error404();
        }

        if ($this->cms_user->is_logged && !$this->cms_user->is_admin) {
            $this->redirectToHome();
        }


        cmsCore::loadControllerLanguage('auth');

        //тут случайно
        if (!cmsUser::isSessionSet('zauth:socid') && !$pass_token) {
            $this->informUserAndHome();
        }

        //мыло не указал
        if (!cmsUser::isSessionSet('zauth:email') && !$pass_token) {
            $this->redirectToAction('finish');
        }

        //если мыло указал таки
        $reg_user = false;
        $email = false;
        if (cmsUser::isSessionSet('zauth:email') && !$pass_token) {

            $email = cmsUser::sessionGet('zauth:email');

            if (!$this->request->has('submit')) {
                cmsUser::addSessionMessage(sprintf(LANG_ZAUTH_NEED_VERIFY, $email), 'info');
            }

            //а есть ли он в списках
            $reg_user = $this->model->getUserTokenByEmail($email);
            if ($reg_user) {
                $reg_user['resubmit_extime'] = modelAuth::RESUBMIT_TIME - (time() - strtotime($reg_user['date_token']));
                if ($reg_user['resubmit_extime'] < 0) {
                    $reg_user['resubmit_extime'] = 0;
                }
            }
            //а если в списках нет, и это странно
            else {
                cmsUser::sessionUnset('zauth:email');
                $this->redirectToAction('finish');
            }
        }

        $form = $this->getForm('verify', array($reg_user));

        $data = array('email_token' => $pass_token);

        if ($this->request->has('submit')) {

            $data = $form->parse($this->request, true);

            $errors = $form->validate($this, $data);

            if (!$errors) {

                $token    = $data['email_token'];
                $reg_user = $this->model->getUserByToken($token); //запись в zusers_token

                $user = $this->model->getUserByEmail($reg_user['email']);

                if (!$reg_user || !$user) {
                    $errors['email_token'] = LANG_VERIFY_EMAIL_ERROR;
                }
            }

            if (!$errors) {

                //зарегистрируем связь
                $this->model->insert('zusers', [
                    'user_id' => $user['id'],
                    'soc'     => $reg_user['soc'],
                    'soc_id'  => $reg_user['soc_id']
                ]);

                //удалим токен
                $this->model->delete('zusers_tokens', $reg_user['id']);

                //уведомим пользователя
                $this->verifiedEmail($user['email'], $reg_user['soc']);

                //забудем данные в сессии
                cmsUser::sessionUnset('zauth');

                //авторизуем пользователя
                $this->authorization($user);
            }

            if ($errors) {
                cmsUser::addSessionMessage(LANG_FORM_ERRORS, 'error');
            }
        }

        return $this->cms_template->render('verify', array(
                    'reg_email' => $email,
                    'data'      => $data,
                    'form'      => $form,
                    'errors'    => isset($errors) ? $errors : false
        ));

    }

}
