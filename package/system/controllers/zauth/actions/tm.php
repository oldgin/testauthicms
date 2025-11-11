<?php
//
class actionZauthTm extends cmsAction {

    public function run() {

        //обработка ошибок
        $error = $this->request->get('error','');
        if ($error) {
            $this->reportError(html_clean($this->request->get('error_description','')),'tm');
            if($error == "access_denied"){
                $this->informUserAndHome('Для входа на сайт необходимо дать доступ нашему приложению');
            }
            $this->informUserAndHome();
        }

        //получим опции доступа
        $tm_bot_name = $this->options['tm_bot_name'];
        $tm_bot_token = $this->options['tm_bot_token'];

        //а также сформируем доп. параметр ссылку на наш сайт
        $redirect_uri = href_to_abs('zauth', 'tm');

        //в поисках hash, который tm отправляет после согласия пользователя на доступ
        $hash = $this->request->get('hash',false);

        //если это первый запрос
        if (!$hash) {
            $js_code = $this->getJSTmCode($tm_bot_name,$redirect_uri);
            return $this->cms_template->render('tm',[
                'js_code' => $js_code
            ]);
        }//!hash
        //если второй запрос
        if (!empty($hash)) {

            //сформить дата массив из данных реквеста
            $data = $this->getTmData();

            //проверить их с помощью телеграм функции
            if(!$this->checkTelegramAuthorization($_GET,$tm_bot_token)){
                $this->reportError('Данные не прошли проверку и возможно подделаны');
                $this->informUserAndHome();
            }

            $soc_id = $data['id'];

            //если авторизован, значит повторная привязка
            $this->checkForBind('tm',$soc_id);

            //проверить, а есть ли юзер среди зарегистрированных
            $is_user = $this->model->getUserZauth('tm',$soc_id);
            if($is_user){
                //тогда авторизуем
                $user = $this->model->getItemById('{users}',$is_user['user_id']);
                $this->authorization($user);
            }

            //запомним данные telegram в сессии и отправимся в экшн финиш
            $this->tmPrepareInfo($data);
            $this->redirectToAction('finish');

        }//!empty($hash)
    }

    public function getJSTmCode($tm_bot_name,$redirect_uri) {
        return '<script async src="https://telegram.org/js/telegram-widget.js?21" data-telegram-login="'. $tm_bot_name .'" data-size="large" data-auth-url="'. $redirect_uri .'"></script>';
    }

    public function checkTelegramAuthorization($auth_data,$token) {
        $check_hash = $auth_data['hash'];
        unset($auth_data['hash']);
        $data_check_arr = [];
        foreach ($auth_data as $key => $value) {
          $data_check_arr[] = $key . '=' . $value;
        }
        sort($data_check_arr);
        $data_check_string = implode("\n", $data_check_arr);
        $secret_key = hash('sha256', $token, true);
        $hash = hash_hmac('sha256', $data_check_string, $secret_key);
        if (strcmp($hash, $check_hash) !== 0) {
            return false;//throw new Exception('Data is NOT from Telegram');
        }
        if ((time() - $auth_data['auth_date']) > 86400) {
          return false;//throw new Exception('Data is outdated');
        }
        return true;
    }

}
