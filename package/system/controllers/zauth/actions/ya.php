<?php
// 1. Первоначально надо создать приложение. Идем по ссылке https://oauth.yandex.ru/client/new
// Дайте понятное пользователю название приложению, заполните Ссылка на сайт приложения, поставьте галочку Веб-сервисы, по аналогии укажите callback URL
// Укажите права доступа к API паспорта, как на скриншоте, т.е. доступ к е-майл, логин, портрет
// В самом низу нажмите создать. Вас переместит на страницу с данными приложения
// Запишите ID. Его надо указать в настройках компонента
// В дальнейшем созданное вами приложение будет доступно по ссылке https://oauth.yandex.ru/

class actionZauthYa extends cmsAction {

    public function run() {

        //обработка ошибок
        $error = $this->request->get('error');
        if ($error) {
            $this->reportError(html_clean($this->request->get('error','')),'ya');
            if ($error == "access_denied") {
                $this->informUserAndHome('Для входа на сайт необходимо дать доступ нашему приложению');
            }
            $this->informUserAndHome();
        }

        //другие параметры
        $token = $this->request->get('token');
        $first = $this->request->get('first');

        //если параметров нет, может они в хэше ссылки
        if (!$token && !$first) {
            return $this->cms_template->render('ya');
        }

        //получим опции доступа
        $client_id = $this->options['ya_client_id'];

        //если это первый запрос
        if ($first) {
            $this->yaFirstStep($client_id);
        }

        //если второй запрос
        if ($token) {
            $token = trim(html_clean($token));

            //получим ответ от яндекса с данными пользователя
            $user_info = $this->yaSecondStep($token);

            if (empty($user_info->id)) {
                $this->reportError('Произошла ошибка после получения согласия при получении данных пользователя');
                $this->informUserAndHome();
            }

            $soc_id = $user_info->id;
            $email = $user_info->default_email;

            //если авторизован, значит повторная привязка
            $this->checkForBind('ya',$soc_id);

            //есть ли пользователь среди когда либо зарегистрированных через соц. сеть
            $is_user = $this->model->getUserZauth('ya', $soc_id);
            if(!$is_user){
                $is_user = $this->model->getUserByEmail($email);
                if($is_user){
                    $is_user['user_id'] = $is_user['id'];//для совместимости
                }
            }
            if ($is_user) {
                //тогда авторизуем
                $user = $this->model->getItemById('{users}', $is_user['user_id']);
                $this->authorization($user);
            }

            //далее действуем в зависимости от того, получили е-майл или нет
            if($email){

                //если пользователя нет, зарегистрируем
                $user_info = $this->yaPrepareInfo($user_info);

                //зарегистрируем пользователя
                $this->addUser($email, array(
                    'soc' => 'ya',
                    'soc_id' => $soc_id
                        ), $user_info);

                //получим данные пользователя
                $user = $this->model->getUserByEmail($email);

                //авторизуем пользователя
                $this->authorization($user);

            }else{
                //запомним данные ya в сессии и отправимся экшн финиш
                $this->yaPrepareInfo($user_info,true);
                $this->redirectToAction('finish');

            }
        }
    }

}
