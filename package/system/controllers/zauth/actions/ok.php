<?php

class actionZauthOk extends cmsAction {

    public function run() {

        //обработка ошибок
        $error = $this->request->get('error');
        if ($error) {
            $this->reportError('access_denied','ok');
            if($error == "access_denied"){
                $this->informUserAndHome('Для входа на сайт необходимо дать доступ нашему приложению');
            }
            $this->informUserAndHome();
        }

        //получим опции доступа
        $client_id = $this->options['ok_client_id'];
        $client_secret = $this->options['ok_client_secret'];
        $client_public = $this->options['ok_client_public'];

        //а также сформируем доп. параметр ссылку на наш сайт
        $redirect_uri = href_to_abs('zauth', 'ok');

        //в поисках code, который вк отправляет после согласия пользователя на доступ
        $code = $this->request->get('code');

        //если это первый запрос
        if (!$code) {
           $this->okFirstStep($client_id,$redirect_uri);
        }//!code
        //если второй запрос
        if (!empty($code)) {

            $code = trim(html_clean($code));

                        //получим ответ от ok с токеном
            $answer = $this->okSecondStep($client_id,$client_secret,$code,$redirect_uri);

            if (isset($answer->error)) {
                //ошибка авторизации
                $this->reportError(html_clean($answer->error_description));
                $this->informUserAndHome();
            }

                        if(empty($answer->access_token)){
                $this->informUserAndHome();
            }

                        $token = $answer->access_token;

            //получим инфу о пользователе
            $user_info = $this->okThirdStep($token,$client_secret,$client_public);

            if (empty($user_info->uid)) {
                $this->reportError('Одноклассники выдали токен, но не вернули данные пользователя');
                $this->informUserAndHome();
            }

            $soc_id = $user_info->uid;

            //если авторизован, значит повторная привязка
            $this->checkForBind('ok',$soc_id);

            //пошла обработка полученных данных
            //есть ли пользователь среди когда либо зарегистрированных через соц. сеть
            $is_user = $this->model->getUserZauth('ok',$soc_id);
            if($is_user){
                //тогда авторизуем
                $user = $this->model->getItemById('{users}',$is_user['user_id']);
                $this->authorization($user);
            }

                        //если пользователя нет, то начинаются страдашки :)
            //запомним данные ок в сессии и отправимся экшн финиш
            $this->okPrepareInfo($user_info,true);
            $this->redirectToAction('finish');


                    }//!empty($code)
    }

}
