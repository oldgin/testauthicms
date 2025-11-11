<?php
//1. https://console.developers.google.com/
//Жмем Create Project
//2. На открывшейся странице даем проекту название
//3. Затем через меню открываем API и сервисы, там Панель управления, и попадаем на страницу https://console.developers.google.com/apis/dashboard
//4. Слева выбираем Учетные данные, затем справа Окно запроса доступа OAuth
//В этом окне надо указать
//- название приложения
//- выбрать адрес электронной почты службы поддержки
//- домен в списке авторизованных доменов
//Жмем сохранить
//5. Вас переместит на вкладку Учетные данные. На вкладке создайте учетные данные. Выберите вариант «Идентификатор клиента OAuth».
//В открывшемся окне выберите "Веб-приложение", и в самом низу в списке разрешенные URL перенаправления укажите https://bergorod.ru/zauth/gl заменив bergorod.ru на свой домен
//Жмем Создать
//6. В открывшемся окне скопируйте значения «Ваш идентификатор клиента» и «Ваш секрет клиента»
//
//Ожидайте, пока Состояние подтверждения сменится на подтверждено.
//Проверять Состояние подтверждения можно:
//- выбираем проект
//- в меню слева выбираем API и сервисы, там Панель управления
//- слева выбираем Учетные данные, затем справа Окно запроса доступа OAuth
//
//У неподтвержденного приложения лимит на 100 запросов к апи

class actionZauthGl extends cmsAction {

    public function run() {

        //обработка ошибок
        $error = $this->request->get('error');
        if ($error) {
            $this->reportError(html_clean($this->request->get('error_description','')),'gl');
            if($error == "access_denied"){
                $this->informUserAndHome('Для входа на сайт необходимо дать доступ нашему приложению');
            }
            $this->informUserAndHome();
        }

        //получим опции доступа
        $client_id = $this->options['gl_client_id'];
        $client_secret = $this->options['gl_client_secret'];

        //а также сформируем доп. параметр ссылку на наш сайт
        $redirect_uri = href_to_abs('zauth', 'gl');

        //в поисках code, который гугл отправляет после согласия пользователя на доступ
        $code = $this->request->get('code');

        //если это первый запрос
        if (!$code) {
           $this->glFirstStep($client_id,$redirect_uri);
        }//!code
        //если второй запрос
        if (!empty($code)) {

            $code = trim(html_clean($code));

                        //получим ответ от гугла с токеном
            $answer = $this->glSecondStep($client_id,$client_secret,$code,$redirect_uri);

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
            $user_info = $this->glThirdStep($token);

                        if(!empty($user_info->error)){
                $this->reportError(html_clean($user_info->error->error_msg));
                $this->informUserAndHome();
            }

            $soc_id = $user_info->id;
            $email = $user_info->email;

            //если авторизован, значит повторная привязка
            $this->checkForBind('gl',$soc_id);

            //есть ли пользователь среди когда либо зарегистрированных через соц. сеть
            $is_user = $this->model->getUserZauth('gl',$soc_id);
            if(!$is_user){
                $is_user = $this->model->getUserByEmail($email);
                if($is_user){
                    $is_user['user_id'] = $is_user['id'];//для совместимости
                }
            }
            if($is_user){
                //тогда авторизуем
                $user = $this->model->getItemById('{users}',$is_user['user_id']);
                $this->authorization($user);
            }

                            //если пользователя нет, зарегистрируем
            $user_info = $this->glPrepareInfo($user_info);

            //зарегистрируем пользователя
            $this->addUser($email,array(
                'soc' => 'gl',
                'soc_id' => $soc_id
            ),$user_info);


            //получим данные пользователя
            $user = $this->model->getUserByEmail($email);

            //авторизуем пользователя
            $this->authorization($user);
        }//!empty($code)
    }

}
