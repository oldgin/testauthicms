<?php

class actionZauthFinish extends cmsAction {

    /**
     * экшн используется для случаев, когда провайдер соц. сети не отдает мыло
     * например, одноклассники
     * на входе имеем пользователя с данными в сессии:
     * zauth:soc
     * zauth:socid
     * zauth:name
     * zauth:photo
     */
    public function run() {

        //тут случайно
        if (!cmsUser::isSessionSet('zauth:socid')) {
            $this->informUserAndHome();
        }

        //если есть параметр в ссылке, забудем мыло и спросим снова
        if ($this->request->get('clean_email')) {
            cmsUser::sessionUnset('zauth:email');
            $this->redirectToAction('finish');
        }

        //уже был здесь
        if (cmsUser::isSessionSet('zauth:email')) {
            $this->redirectToAction('verify');
        }

        $errors = false;
        $item   = false;
        $form   = $this->getForm('email');
        cmsCore::loadControllerLanguage('auth');


        if ($this->request->has('submit')) {
            $item   = $form->parse($this->request, true);
            $errors = $form->validate($this, $item);

            if ($errors) {
                cmsUser::addSessionMessage(LANG_FORM_ERRORS, 'error');
            } else {
                //доп. проверки
                $email   = $item['email'];
                $is_user = $this->model->getUserByEmail($email);
                if ($is_user) {

                    if ($this->options['verify']) {
                        cmsUser::sessionSet('zauth:email', $email);
                        $this->generateToken($email, cmsUser::sessionGet('zauth:soc'), cmsUser::sessionGet('zauth:socid'));
                        $this->redirectToAction('verify');
                    } else {
                        cmsUser::addSessionMessage(LANG_FORM_ERRORS, 'error');
                        $errors['email'] = 'Такое значение уже используется';
                    }
                }
                if (!$errors) {
                    //если нет, то добавим
                    //подготовим данные
                    $user_info = $this->PrepareInfo();

                    //зарегистрируем пользователя
                    $this->addUser($email, array(
                        'soc'    => $user_info['soc'],
                        'soc_id' => $user_info['socid']
                            ), $user_info, true);


                    //получим данные пользователя
                    $user = $this->model->getUserByEmail($email);

                    //авторизуем пользователя
                    $this->authorization($user);
                }
            }
        }




        return $this->cms_template->render('finish', array(
                    'form'   => $form,
                    'item'   => $item,
                    'errors' => $errors
        ));

    }

}
