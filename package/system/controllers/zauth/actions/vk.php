<?php

class actionZauthVk extends cmsAction {

    public function run() {

        //обработка ошибок
        $error = $this->request->get('error');
        if ($error) {
            $this->reportError(html_clean($this->request->get('error_description','')),'vk');
            if($error == "access_denied"){
                $this->informUserAndHome('Для входа на сайт необходимо дать доступ нашему приложению');
            }
            $this->informUserAndHome();
        }

        //получим опции доступа
        $client_id = $this->options['vk_client_id'];
        $client_secret = $this->options['vk_client_secret'];

        //а также сформируем доп. параметр ссылку на наш сайт
        $redirect_uri = href_to_abs('zauth', 'vk');

        //в поисках code, который вк отправляет после согласия пользователя на доступ
        $code = $this->request->get('code');

        //если это первый запрос
        if (!$code) {
           $this->vkFirstStep($client_id,$redirect_uri);
        }//!code
        //если второй запрос
        if (!empty($code)) {

            $code = trim(html_clean($code));

                        //получим ответ от вк с первичными данными пользователя
            $answer = $this->vkSecondStep($client_id,$client_secret,$code,$redirect_uri);

            if (isset($answer->error)) {
                //ошибка авторизации
                $this->reportError(html_clean($answer->error_description));
                $this->informUserAndHome();
            }

            //пошла обработка полученных данных
            if (empty($answer->access_token)) {
                $this->informUserAndHome();
            }

            $email = empty($answer->email) ? false : $answer->email;
            $token = $answer->access_token;
            $soc_id = $answer->user_id;//понадобиться для хранения пользователя в нашей базе зареганых

            //если авторизован, значит повторная привязка
            $this->checkForBind('vk',$soc_id);

            //получим данные о пользователе с помощью токена
            $user_info = $this->vkThirdStep($soc_id,$token);

                        if(!empty($user_info->error)){
                $this->reportError(html_clean($user_info->error->error_msg));
                $this->informUserAndHome();
            }

                        //есть ли пользователь среди когда либо зарегистрированных через соц. сеть
            $is_user = $this->model->getUserZauth('vk',$soc_id);
            if($is_user){
                //тогда авторизуем
                $user = $this->model->getItemById('{users}',$is_user['user_id']);
                $this->authorization($user);
            }

                        //далее действуем в зависимости от того, получили е-майл или нет
            if($email){

                                //если пользователя нет, зарегистрируем
                if(!$this->model->getUserByEmail($email)){

                                    $user_info = $this->vkPrepareInfo($user_info);

                    //зарегистрируем пользователя
                    $this->addUser($email,array(
                        'soc' => 'vk',
                        'soc_id' => $soc_id
                    ),$user_info);

                                }

                                //получим данные пользователя
                $user = $this->model->getUserByEmail($email);

                //авторизуем пользователя
                $this->authorization($user);

                            }else{

                                //запомним данные vk в сессии и отправимся экшн финиш
                $this->vkPrepareInfo($user_info,true);
                $this->redirectToAction('finish');

                            }
        }//!empty($code)
    }

}
