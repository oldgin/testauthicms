<?php

class actionZauthMr extends cmsAction {

    public function run() {

        //обработка ошибок
        $error = $this->request->get('error');
        if ($error) {
            $this->reportError(html_clean($this->request->get('error','')),'mr');
            if ($error == "access_denied") {
                $this->informUserAndHome('Для входа на сайт необходимо дать доступ нашему приложению');
            }
            $this->informUserAndHome();
        }

        //получим опции доступа
        $client_id     = $this->options['mr_client_id'];
        $client_secret = $this->options['mr_client_secret'];

        //а также сформируем доп. параметр ссылку на наш сайт
        $redirect_uri = href_to_abs('zauth', 'mr');

        //в поисках code, который гугл отправляет после согласия пользователя на доступ
        $code = $this->request->get('code');

        //если это первый запрос
        if (!$code) {
            $this->mrFirstStep($client_id, $redirect_uri);
        }//!code
        //если второй запрос
        if (!empty($code)) {

            $code = trim(html_clean($code));

            //проверим state
            $state = $this->request->get('state');
            if(!$this->mrCheckState($state)){
                $this->informUserAndHome();
            }

            //получим ответ с токеном
            $answer = $this->mrSecondStep($client_id, $client_secret, $code, $redirect_uri);

            if (isset($answer->error)) {
                //ошибка авторизации
                $this->reportError(html_clean($answer->error_description));
                $this->informUserAndHome();
            }

            //пошла обработка полученных данных
            if (empty($answer->access_token)) {
                $this->informUserAndHome();
            }

            //токен
            $token = $answer->access_token;

            //получим данные о пользователе с помощью токена
            $user_info = $this->mrThirdStep($token);

            if (!empty($user_info->error)) {
                $this->reportError(html_clean($user_info->error->error_msg));
                $this->informUserAndHome();
            }

            $soc_id = $user_info->id;
            $email  = $user_info->email;

            //если авторизован, значит повторная привязка
            $this->checkForBind('mr',$soc_id);

            //есть ли пользователь среди когда либо зарегистрированных через соц. сеть
            $is_user = $this->model->getUserZauth('mr', $soc_id);
            if (!$is_user) {
                $is_user = $this->model->getUserByEmail($email);
                if ($is_user) {
                    $is_user['user_id'] = $is_user['id']; //для совместимости
                }
            }
            if ($is_user) {
                //тогда авторизуем
                $user = $this->model->getItemById('{users}', $is_user['user_id']);
                $this->authorization($user);
            }

            //если пользователя нет, зарегистрируем
            $user_info = $this->mrPrepareInfo($user_info);

            //если поле nick в выдаче майл.ру пусто
            if (empty($user_info['nickname'])) {
                $user_info['nickname'] = strstr($email, '@', true);
            }

            //зарегистрируем пользователя
            $this->addUser($email, array(
                'soc'    => 'mr',
                'soc_id' => $soc_id
                    ), $user_info);


            //получим данные пользователя
            $user = $this->model->getUserByEmail($email);

            //авторизуем пользователя
            $this->authorization($user);
        }//!empty($code)

    }

}
