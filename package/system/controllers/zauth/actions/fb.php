<?php

class actionZauthFb extends cmsAction {

    public function run() {

        //обработка ошибок
        $error = $this->request->get('error');
        if ($error) {
            $this->reportError(html_clean($this->request->get('error_description','')),'fb');
            if($error == "access_denied"){
                $this->informUserAndHome('Для входа на сайт необходимо дать доступ нашему приложению');
            }
            $this->informUserAndHome();
        }

        //получим опции доступа
        $client_id = $this->options['fb_client_id'];
        $client_secret = $this->options['fb_client_secret'];

        //а также сформируем доп. параметр ссылку на наш сайт
        $redirect_uri = href_to_abs('zauth', 'fb');

        //в поисках code, который fb отправляет после согласия пользователя на доступ
        $code = $this->request->get('code');

        //если это первый запрос
        if (!$code) {
            $this->fbFirstStep($client_id, $redirect_uri);
        }//!code
        //если второй запрос
        if (!empty($code)) {

            $code = trim(html_clean($code));

            //получим ответ от fb с первичными данными пользователя
            $answer = $this->fbSecondStep($client_id, $client_secret, $code, $redirect_uri);

            if (isset($answer->error)) {
                //ошибка авторизации
                $this->reportError(html_clean($answer->error->message));
                $this->informUserAndHome();
            }

            //пошла обработка полученных данных
            if (empty($answer->access_token)) {
                $this->informUserAndHome();
            }

                        $token = $answer->access_token;

            //получим данные о пользователе fb с помощью токена
            $user_info = $this->fbThirdStep($token);

            if (empty($user_info->id)) {
                $this->reportError('Фейсбук выдал токен, но не вернул данные пользователя');
                $this->informUserAndHome();
            }

            $soc_id = $user_info->id;//понадобиться для хранения пользователя в нашей базе зареганых

            //если авторизован, значит повторная привязка
            $this->checkForBind('fb',$soc_id);

            //есть ли пользователь среди когда либо зарегистрированных через соц. сеть
            $is_user = $this->model->getUserZauth('fb',$soc_id);
            if($is_user){
                //тогда авторизуем
                $user = $this->model->getItemById('{users}',$is_user['user_id']);
                $this->authorization($user);
            }

                        //далее действуем в зависимости от того, получили е-майл или нет
            if(!empty($user_info->email)){
                //подготовим данные
                $email = $user_info->email;

                                //если пользователя нет, зарегистрируем
                if(!$this->model->getUserByEmail($email)){

                    $user_info = $this->fbPrepareInfo($user_info);

                    //зарегистрируем пользователя
                    $this->addUser($email,array(
                        'soc' => 'fb',
                        'soc_id' => $soc_id
                    ),$user_info);

                                }

                                //получим данные пользователя
                $user = $this->model->getUserByEmail($email);

                //авторизуем пользователя
                $this->authorization($user);
            }else{

                                //запомним данные fb в сессии и отправимся экшн финиш
                $this->fbPrepareInfo($user_info,true);
                $this->redirectToAction('finish');
            }

        }//!empty($code)
    }

}
