<?php

class zauth extends cmsFrontend {

    protected $useOptions = true;
    private $providers = ['vk','ok','fb','ya','gl','mr','tm'];
    protected $vk_api_version = '5.130';

    /**
     * общие
     */
    public function before($action_name) {

        parent::before($action_name);

        //проверим опции
        $providers = $this->providers;
        if (in_array($action_name, $providers)) {
            //если соц. сеть отключена, вернем назад
            if (!$this->options[$action_name . '_on']) {
                $this->redirectBack();
            }
            //сохраним ссылку, с которой пришли, в backurl
            $this->saveBackUrl();
        }

        return true;
    }

    public function getProviders() {
        return array_combine($this->providers, [
            LANG_ZAUTH_PROVIDERS_VK,
            LANG_ZAUTH_PROVIDERS_OK,
            LANG_ZAUTH_PROVIDERS_FB,
            LANG_ZAUTH_PROVIDERS_YA,
            LANG_ZAUTH_PROVIDERS_GL,
            LANG_ZAUTH_PROVIDERS_MR,
            LANG_ZAUTH_PROVIDERS_TM
        ]);
    }

    public function getCurl($link, $params = false, $headers = false) {
        $ch = curl_init(); //открытие сеанса
        curl_setopt($ch, CURLOPT_URL, $link); //какой урл откроем
        curl_setopt($ch, CURLOPT_HEADER, 0); //откажемся принимать заголовки
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //укажем, что надо вернуть содержимое
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; ru; rv:1.9.1.5) Gecko/20091102 Firefox/3.5.5 GTB6");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        if ($params) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        }
        if($headers){
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        }
        $result = curl_exec($ch); //попросим курл выполнить запрос
        if ($result === false) {
            return 'Ошибка curl: ' . curl_error($ch);
        }

        return $result;
    }

    public function reportError($text,$provider = false) {
        if (!empty($this->options['notice_admin'])) {
            if ($this->options['notice_admin']) {

                $messenger = cmsCore::getController('messages');
                $messenger->addRecipient(1);
                $text = "При попытке регистрации пользователя была получена ошибка: " . html($text,false);
                if($provider){
                    $providers = $this->getProviders();
                    if(!empty($providers[$provider])){
                        $text = '[' . $providers[$provider] . '] ' . $text;
                    }
                }

                $message = array(
                    'content' => $text
                );
                $messenger->sendNoticePM($message);
            }
        }
    }

    public function informUserAndHome($text = false) {
        if (!$text) {
            $text = LANG_ZAUTH_ERRORS_INFORM1;
        }
        cmsUser::addSessionMessage($text);
        $this->goByBackLink(false);
    }

    public function informLoggedUserAndHome($text = false) {
        if (!$text) {
            $text = LANG_ZAUTH_ERRORS_INFORM1;
        }
        cmsUser::addSessionMessage($text);
        $this->goByBackLink(true);
    }

    public function addNewBind($soc,$soc_id) {
        $this->model->saveUser(cmsUser::get('id'),$soc,$soc_id);
        cmsUser::addSessionMessage(LANG_ZAUTH_SUCCESS_BINDED, 'success');
        $this->goByBackLink(true);
    }

    public function checkForBind($soc,$soc_id) {
        if(cmsUser::isLogged()){
            if($this->model->getUserZauth($soc,$soc_id)){
                $this->informLoggedUserAndHome(LANG_ZAUTH_USED);
            }
            $this->addNewBind($soc,$soc_id);
        }
    }

    public function goByBackLink($logged = true) {
        $backurl = cmsUser::sessionGet('zauth:backurl',true);
        if($backurl){
            //авторизованных уберем со страницы регистрации и авторизации
            if ($logged){
                if(strpos($backurl, href_to('auth', 'login')) !== false) {
                    $this->redirectToHome();
                }
                if(strpos($backurl, href_to('auth', 'register')) !== false) {
                    $this->redirectToHome();
                }
            }
            $this->redirect($backurl);
        }
        $this->redirectToHome();
    }

    public function sendPasswd($user) {

        $messenger = cmsCore::getController('messages');
        $to = array('email' => $user['email'], 'name' => $user['nickname']);
        //укажем название шаблона письма
        $letter = array('name' => 'reg_zauth');
        //отправим пользователю пароль
        //если is_locked значит пользователю придет еще одно письмо с кодом авторизации
        //уведомим об этом в нашем письме, чтобы не было путаницы
        $attention_text = empty($user['is_locked']) ? " " : LANG_ZAUTH_ERRORS_INFORM2;
        $messenger->sendEmail($to, $letter, array(
            'name' => html_clean($user['nickname']),
            'pass' => $user['password1'],
            'email' => $user['email'],
            'authurl' => href_to_abs('auth', 'login'),
            'attention' => $attention_text
        ));
    }

    /*
     * сохраняет в сессии ссылку на страницу, с которой пришли, для обратного редиректа в informUserAndHome
     */
    public function saveBackUrl() {
        if(!cmsUser::sessionGet('zauth:backurl')){
            cmsUser::sessionSet('zauth:backurl', $this->getBackURL());
        }
    }


    /*
     * добавляет пользователя в базу
     * $email - мыло
     * $soc - array(soc => , soc_id =>)
     * $user_info - array (nickname => , photos => array())
     * $fromfinish - используется для подверждения мыла
     */

    public function addUser($email, $soc, $user_info,$fromfinish=false) {

        //из-за LANG_REG_CFG_VERIFY_LOCK_REASON
        cmsCore::loadLanguage('auth/auth');

        $user = array();
        $user['email'] = $email;
        $user['nickname'] = $user_info ? $user_info['nickname'] : strstr($user['email'], '@', true);
        $passwd = string_random(6);
        $user['password1'] = $passwd;
        $user['password2'] = $passwd;
        $user['groups'] = empty($this->options['groups']) ? false : $this->options['groups'];
        $user['avatar'] = $user_info ? $user_info['photos'] : null;

        //Блокируем пользователя, если включена верификация e-mail, сперто из register.php
        if ($this->controller_auth->options['verify_email'] && $fromfinish){
            $pass_token = hash('sha256', string_random(32, $user['email']));
            $user = array_merge($user, array(
                'is_locked' => true,
                'lock_reason' => LANG_REG_CFG_VERIFY_LOCK_REASON,
                'pass_token' => $pass_token,
                'date_token' => ''
            ));
        }

        $result = $this->model_users->addUser($user);
        if ($result['success']) {
            $user['id'] = $result['id'];
            //отправим пользователю письмо
            $this->sendPasswd($user);
            //на сайт еще не входил
            cmsUser::setUPS('first_auth', 1, $user['id']);
            //запомним в базе
            $this->model->saveUser($user['id'], $soc['soc'], $soc['soc_id']);

            // отправляем письмо верификации e-mail
            if ($this->controller_auth->options['verify_email'] && $fromfinish){
                $this->sendVerify($user);
            }
            //уведомим хуки
            cmsEventsManager::hook('user_registered', $user);
            cmsUser::addSessionMessage(LANG_REG_SUCCESS, 'success');

        } else {
            $this->informUserAndHome('При регистрации пользователя в базе произошла ошибка');
        }
    }

    public function authorization($user) {

        //на всякий пожарный
        if (!$user) {
            $this->reportError('Произошел сбой на последнем шаге при регистрации');
            $this->informUserAndHome("Авторизация невозможна. Не найден пользователь");
        }

        //получим подготовленный массив
        $user = $this->model_users->getUser($user['id']);

        //проверка на блокировку пользователя
        //если заблокирован, сессия cmsUser::setUserSession($user); будет удалена
        cmsEventsManager::hook('auth_login', $user['id']);
        if(!cmsUser::sessionGet('user')){
            $this->redirectToHome();
        }

        //если пользователь ранее удалялся, вернем его
        if($user['is_deleted']){
            $this->model->update('{users}',$user['id'],array(
                'is_deleted' => NULL
            ));
            $user['is_deleted'] = false;
        }

        //авторизуем пользователя по аналогии с user::login
        $user = cmsEventsManager::hook('user_login', $user);
        $user['permissions'] = cmsUser::getPermissions($user['groups']);

        //сперто из login.php
        $is_site_offline = !cmsConfig::get('is_site_on');
        if ($is_site_offline) {
            if (empty($user['permissions']['auth']['view_closed']) && empty($user['is_admin'])) {
                cmsUser::addSessionMessage(LANG_LOGIN_ADMIN_ONLY, 'error');
                return $this->redirectToHome();
            }
        }

        cmsUser::loginComplete($user, true);
        cmsEventsManager::hook('auth_login', $user['id']);

        //редиректим
        cmsUser::addSessionMessage(LANG_ZAUTH_SUCCESS_ENTERED, 'success');

        //если только что зарегался
        if (cmsUser::getUPS('first_auth', $user['id'])) {
            cmsUser::deleteUPS('first_auth', $user['id']);
            $AuthRedirectOptFirst = $this->controller_auth->options['first_auth_redirect'];

            //отправка приветственного сообщения
            $this->controller_auth->sendGreetMsg($user);

            if($AuthRedirectOptFirst == 'none'){
                $this->goByBackLink();
            }
            cmsUser::sessionUnset('zauth:backurl');
            $this->controller_auth->redirect($this->controller_auth->getAuthRedirectUrl($AuthRedirectOptFirst));
        }

        //если не регался, а только авторизовался
        $AuthRedirectOpt = $this->controller_auth->options['auth_redirect'];
        if($AuthRedirectOpt == 'none'){
            $this->goByBackLink();
        }
        cmsUser::sessionUnset('zauth:backurl');
        $this->controller_auth->redirect($this->controller_auth->getAuthRedirectUrl($AuthRedirectOpt));
    }

    /*
     * отправляет письмо о верификации и редиректит на verify
     * сперто из auth/register.php
     */

    public function sendVerify($user) {
        $verify_exp = empty($this->controller_auth->options['verify_exp']) ? 48 : $this->controller_auth->options['verify_exp'];

        $to = array('email' => $user['email'], 'name' => $user['nickname']);
        $letter = array('name' => 'reg_verify');

        $this->controller_messages->sendEmail($to, $letter, array(
            'nickname'    => $user['nickname'],
            'page_url'    => href_to_abs('auth', 'verify', $user['pass_token']),
            'pass_token'  => $user['pass_token'],
            'valid_until' => html_date(date('d.m.Y H:i', time() + ($verify_exp * 3600)), true)
        ));

        cmsUser::addSessionMessage(sprintf(LANG_REG_SUCCESS_NEED_VERIFY, $user['email']), 'info');

        cmsUser::setCookie('reg_email', $user['email'], $verify_exp*3600);

        // редиректим сразу на форму подтверждения регистрации
        $this->redirectTo('auth','verify');
    }

    //токен и е-майл
    public function generateToken($email, $soc, $soc_id) {

        $this->model->filterEqual('email', $email)
                ->filterEqual('soc', $soc)
                ->filterEqual('soc_id', $soc_id)
                ->deleteFiltered('zusers_tokens');

        $pass_token = string_random(32, $email);

        //почистим старые токены
        $verify_exp = $this->options['verify_exp'];
        $exp_date   = date('Y-m-d H:i:s', time() - ($verify_exp * 60));
        $this->model->filterLt('date_token', $exp_date)
                ->deleteFiltered('zusers_tokens');

        $this->model->insert('zusers_tokens', [
            'email'      => $email,
            'soc'        => $soc,
            'soc_id'     => $soc_id,
            'pass_token' => $pass_token
        ]);

        //email
        $user = $this->model->getUserByEmail($email);
        $to     = array('email' => $user['email'], 'name' => $user['nickname']);
        $letter = array('name' => 'reg_zauth_email');

        $this->controller_messages->sendEmail($to, $letter, array(
            'nickname'    => $user['nickname'],
            'page_url'    => href_to_abs('zauth', 'verify', $pass_token),
            'pass_token'  => $pass_token,
            'valid_until' => html_date(date('d.m.Y H:i', time() + ($verify_exp * 60)), true)
        ));

    }

    //verified е-майл
    public function verifiedEmail($email, $soc) {

        $user = $this->model->getUserByEmail($email);
        $to     = array('email' => $user['email'], 'name' => $user['nickname']);
        $letter = array('name' => 'reg_zauth_verified');

        $this->controller_messages->sendEmail($to, $letter, array(
            'nickname'    => $user['nickname'],
            'button'    => constant('LANG_ZAUTH_LINKS_'.strtoupper($soc))
        ));

    }

    /**
     * работа с фото
     */
    public function createAvaDir() {//создаем папку, в которую будем сохранять картинку
        $cfg = cmsConfig::getInstance();
        $dest_dir = $cfg->upload_path . "zauth/";
        @mkdir($dest_dir, 0777, true);
        chmod($dest_dir, 0777); //for u022
        return $dest_dir;
    }

    public function copyAva($link) {//копируем картинку в папку poster по годам
        $dest = $this->createAvaDir();
        $dest_name = $dest . files_sanitize_name($link);
        $this->getPhotoWRedirect($link, $dest_name);
        return $dest_name;
    }

    public function createAvaCopy($image_file) {//создаем копии картинок
        $sizes = $this->getPresetSizes(); //узнаем размеры картинок, заданных в настройках поля photo
        $model_images = cmsCore::getModel('images');
        $presets      = $model_images->filterIn('name', $sizes)->getPresets();

        try {
            $image = new cmsImages($image_file);
        } catch (Exception $exc) {
            files_delete_file($image_file, 2);
            return false;
        }
        $result = [];
        foreach ($presets as $p) {
            $resized_path = $image->resizeByPreset($p, '');
            if (!$resized_path) {
                continue;
            }
            $result[$p['name']] = $resized_path;
        }
        files_delete_file($image_file, 2);
        return $result;
    }

    public function copyAvaWithCopies($link) {//чисто для удобства, копируется постер, создаются копии, возвращается массив ссылок для записи в бд
        if(empty($link)){
            return false;
        }
        $image = $this->copyAva($link);
        return $this->createAvaCopy($image);
    }

    public function getPresetSizes() {
        $options = cmsModel::yamlToArray($this->model->getAvatarOptions());
        return $options['sizes'];
    }

    /*
     * берет инфу из сессии
     * для экшна finish
     */

    public function prepareInfo() {

        $nickname = cmsUser::sessionGet('zauth:name');
        $photos = $this->copyAvaWithCopies(cmsUser::sessionGet('zauth:photo'));

        $user = array(
            'nickname' => $nickname,
            'photos' => $photos,
            'soc' => cmsUser::sessionGet('zauth:soc'),
            'socid' => cmsUser::sessionGet('zauth:socid')
        );

        //удалим сессии
        cmsUser::sessionUnset('zauth');

        return $user;
    }

    /*
     * конструирует ник
     */

    public function makeNickname($first_name, $last_name) {
        $nickname = false;
        $first_name = empty($first_name) ? false : $first_name;
        $last_name = empty($last_name) ? false : $last_name;

        if ($first_name || $last_name) {
            $nickname = implode(' ', array($first_name, $last_name));
        }
        return $nickname;
    }

    /**
     * получаем список доступных соц. сетей
     */
    public function getlinks() {
        $links['vk'] = $this->getOption('vk_on');
        $links['ok'] = $this->getOption('ok_on');
        $links['fb'] = $this->getOption('fb_on');
        $links['ya'] = $this->getOption('ya_on');
        $links['gl'] = $this->getOption('gl_on');
        $links['mr'] = $this->getOption('mr_on');
        $links['tm'] = $this->getOption('tm_on');
        return $links;
    }

    //like file_save_from_url in files.helper.php
    public function getPhotoWRedirect($url, $destination) {

        if (!function_exists('curl_init')) {
            return false;
        }

        $dest_file = @fopen($destination, "w");

        $curl = curl_init();
        if (strpos($url, 'https') === 0) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        }
        curl_setopt($curl, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS | CURLPROTO_HTTP);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/81.0.4044.138 Safari/537.36 OPR/68.0.3618.173");
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FILE, $dest_file);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_exec($curl);
        curl_close($curl);
        fclose($dest_file);

        return true;
    }

    /*
     * vk -->
     */

    /*
     * первоначальный запрос к вк
     * в ответ получим code параметром в get
     * нужный для шага 2
     */

    public function vkFirstStep($client_id, $redirect_uri) {

        $params = array(
            'client_id' => $client_id,
            'redirect_uri' => $redirect_uri,
            'display' => 'page',
            'scope' => 'email',
            'response_type' => 'code',
            'v' => $this->vk_api_version
        );
        $oauth_url = 'https://oauth.vk.com/authorize?' . urldecode(http_build_query($params));
        $this->redirect($oauth_url);
    }

    /*
     * второй запрос к вк
     * в ответ получим объект $answer->{access_token,expires_in,user_id,email}
     */

    public function vkSecondStep($client_id, $client_secret, $code, $redirect_uri) {
        $params = array(
            'client_id' => $client_id,
            'client_secret' => $client_secret,
            'code' => $code,
            'redirect_uri' => $redirect_uri,
            'v' => $this->vk_api_version
        );

        $url = 'https://oauth.vk.com/access_token';
        $answer = $this->getCurl($url, $params);

        //ответ от вк
        return json_decode($answer);
    }

    /*
     * третий запрос к вк
     * в ответ ожидаем получить данные пользователя
     * $answer->response[0]->{id,first_name,last_name,photo_200}
     */

    public function vkThirdStep($soc_id, $token) {

        $params = array(
            'uids' => $soc_id,
            'fields' => 'first_name,last_name,photo_max_orig',
            'access_token' => $token,
            'v' => $this->vk_api_version
        );

        $url = 'https://api.vk.com/method/users.get';
        $answer = $this->getCurl($url, $params);

        //ответ от вк
        return json_decode($answer);
    }

    /*
     * преобразует объект $answer->response[0]->{id,first_name,last_name,photo_200}
     * в массив [nickname,[photo]]
     */

    public function vkPrepareInfo($user_info,$tosession=false) {

        if($tosession){
            cmsUser::sessionSet('zauth:soc', 'vk');
            cmsUser::sessionSet('zauth:socid', $user_info->response[0]->id);
            cmsUser::sessionSet('zauth:name', $this->makeNickname($user_info->response[0]->first_name,$user_info->response[0]->last_name));
            cmsUser::sessionSet('zauth:photo', $user_info->response[0]->photo_max_orig);
        }else{
            $nickname = false;
            $first_name = empty($user_info->response[0]->first_name) ? false : $user_info->response[0]->first_name;
            $last_name = empty($user_info->response[0]->last_name) ? false : $user_info->response[0]->last_name;

            if ($first_name || $last_name) {
                $nickname = implode(' ', array($first_name, $last_name));
            }

            $photos = $this->copyAvaWithCopies($user_info->response[0]->photo_max_orig);

            return array(
                'nickname' => $nickname,
                'photos' => $photos
            );
        }
    }

    /*
     * vk <--
     */

    /*
     * ok -->
     */

    /*
     * первоначальный запрос к ок
     * в ответ получим code параметром в get
     * нужный для шага 2
     */

    public function okFirstStep($client_id, $redirect_uri) {

        $params = array(
            'client_id' => $client_id,
            'redirect_uri' => $redirect_uri,
            'scope' => 'GET_EMAIL',
            'response_type' => 'code'
        );
        $oauth_url = 'https://connect.ok.ru/oauth/authorize?' . urldecode(http_build_query($params));
        $this->redirect($oauth_url);
    }

    /*
     * второй запрос к ок
     * в ответ получим объект $answer->{access_token,token_type,refresh_token,expires_in}
     */

    public function okSecondStep($client_id, $client_secret, $code, $redirect_uri) {
        $params = array(
            'client_id' => $client_id,
            'client_secret' => $client_secret,
            'code' => $code,
            'redirect_uri' => $redirect_uri,
            'grant_type' => 'authorization_code'
        );

        $url = 'https://api.ok.ru/oauth/token.do';
        $answer = $this->getCurl($url, urldecode(http_build_query($params)));

        //ответ от ok
        return json_decode($answer);
    }

    /*
     * третий запрос к ок
     * в ответ приходит инфо о пользователе
     */

    public function okThirdStep($token, $client_secret, $client_public) {

        //вычислим подпись
        $secret_key = md5($token . $client_secret);
        $sig = md5("application_key={$client_public}format=jsonmethod=users.getCurrentUser{$secret_key}");

        $params = array(
            'application_key' => $client_public,
            'format' => 'json',
            'method' => 'users.getCurrentUser',
            'sig' => $sig,
            'access_token' => $token
        );

        $url = 'https://api.ok.ru/fb.do';
        $answer = $this->getCurl($url, urldecode(http_build_query($params)));

        //ответ от ok
        return json_decode($answer);
    }

    /*
     * сохраняет в сессии данные пользователя
     */

    public function okPrepareInfo($user_info,$tosession=false) {

        if($tosession){
            cmsUser::sessionSet('zauth:soc', 'ok');
            cmsUser::sessionSet('zauth:socid', $user_info->uid);
            cmsUser::sessionSet('zauth:name', $user_info->name);
            cmsUser::sessionSet('zauth:photo', $user_info->pic_3);
        }
    }

    /*
     * ok <--
     */

    /*
     * fb -->
     */

    /*
     * первоначальный запрос к fb
     * в ответ получим code параметром в get
     * нужный для шага 2
     */

    public function fbFirstStep($client_id, $redirect_uri) {

        $params = array(
            'client_id' => $client_id,
            'redirect_uri' => $redirect_uri,
            'scope' => 'public_profile,email',
            'response_type' => 'code'
        );
        $oauth_url = 'https://www.facebook.com/dialog/oauth?' . urldecode(http_build_query($params));
        $this->redirect($oauth_url);
    }

    /*
     * второй запрос к fb
     * в ответ получим объект $answer->{access_token,expires_in,token_type}
     */

    public function fbSecondStep($client_id, $client_secret, $code, $redirect_uri) {
        $params = array(
            'client_id' => $client_id,
            'client_secret' => $client_secret,
            'code' => $code,
            'redirect_uri' => $redirect_uri
        );

        $url = 'https://graph.facebook.com/oauth/access_token?';
        $answer = $this->getCurl($url, urldecode(http_build_query($params)));

        //ответ от вк
        return json_decode($answer);
    }

    /*
     * третий запрос к fb
     * в ответ ожидаем получить данные пользователя
     * $answer->{id,first_name,last_name,email,picture->{data->{height,is_silhouette,url,width}}}
     */

    public function fbThirdStep($token) {

        $params = array(
            "access_token" => $token,
            "fields" => "id,first_name,last_name,picture.width(200).height(200),email"
        );

        $url = 'https://graph.facebook.com/me?';
        $answer = $this->getCurl($url, urldecode(http_build_query($params)));

        //ответ от fb
        return json_decode($answer);
    }

    /*
     * преобразует объект $answer->{id,first_name,last_name,email,picture->{data->{height,is_silhouette,url,width}}}
     * в массив [nickname,[photos]]
     */

    public function fbPrepareInfo($user_info,$tosession=false) {

        if($tosession){
            cmsUser::sessionSet('zauth:soc', 'fb');
            cmsUser::sessionSet('zauth:socid', $user_info->id);
            cmsUser::sessionSet('zauth:name', $this->makeNickname($user_info->first_name,$user_info->last_name));
            cmsUser::sessionSet('zauth:photo', $user_info->picture->data->url);
        }else{
            $first_name = empty($user_info->first_name) ? false : $user_info->first_name;
            $last_name = empty($user_info->last_name) ? false : $user_info->last_name;

            if ($first_name || $last_name) {
                $nickname = implode(' ', array($first_name, $last_name));
            }

            $photos = $this->copyAvaWithCopies($user_info->picture->data->url);

            return array(
                'nickname' => $nickname,
                'photos' => $photos
            );
        }
    }

    /*
     * fb <--
     */

    /*
     * ya -->
     */

    /*
     * формирует ссылку для перехода и получения токена
     */

    public function yaFirstStep($client_id) {

        $params = array(
            'response_type' => 'token',
            'client_id' => $client_id,
        );
        $oauth_url = 'https://oauth.yandex.ru/authorize?' . urldecode(http_build_query($params));
        $this->redirect($oauth_url);
    }

    /*
     * получает данные о пользователе
     * array(first_name,last_name,display_name,emails[0],default_avatar_id,default_email,real_name,is_avatar_empty,client_id,login,sex,id);
     */

    public function yaSecondStep($token) {

        $url = 'https://login.yandex.ru/info';
        $headers[] = "Authorization: OAuth $token";

        $answer = $this->getCurl($url, false, $headers);

        //ответ от яндекс
        return json_decode($answer);

    }

    public function yaPrepareInfo($user_info,$tosession = false) {

        if($tosession){
            cmsUser::sessionSet('zauth:soc', 'ya');
            cmsUser::sessionSet('zauth:socid', $user_info->id);
            cmsUser::sessionSet('zauth:name', $user_info->real_name);
            cmsUser::sessionSet('zauth:photo', "https://avatars.yandex.net/get-yapic/{$user_info->default_avatar_id}/islands-200");
        }else{
            $nickname = $user_info->real_name;
            $photos = $this->copyAvaWithCopies("https://avatars.yandex.net/get-yapic/{$user_info->default_avatar_id}/islands-200");

            return array(
                'nickname' => $nickname,
                'photos' => $photos
            );
        }
    }

    /*
     * ya <--
     */

        /*
     * gl -->
     */

    /*
     * формирует ссылку для перехода и получения кода
     */

    public function glFirstStep($client_id,$redirect_uri) {

        $params = array(
            'response_type' => 'code',
            'client_id' => $client_id,
            'redirect_uri'  => $redirect_uri,
            //'scope' => 'https://www.googleapis.com/auth/userinfo.email https://www.googleapis.com/auth/userinfo.profile'
            'scope' => 'email profile'
        );
        $oauth_url = 'https://accounts.google.com/o/oauth2/auth?' . urldecode(http_build_query($params));
        $this->redirect($oauth_url);
    }

    /*
     * получает токен токена
     */

    public function glSecondStep($client_id,$client_secret,$code,$redirect_uri) {

        $params = array(
            'grant_type' => 'authorization_code',
            'client_id' => $client_id,
            'client_secret' => $client_secret,
            'redirect_uri'  => $redirect_uri,
            'code' => $code
        );
        $url = 'https://oauth2.googleapis.com/token?' . urldecode(http_build_query($params));

        $answer = $this->getCurl($url, $params);

        return json_decode($answer);

    }

    /*
     * получает данные о пользователе
     * array(id,email,verified_email,name,given_name,family_name,picture,locale);
     */

    public function glThirdStep($token) {

        $params = array(
            'access_token' => $token
        );
        $url = 'https://www.googleapis.com/oauth2/v2/userinfo?' . urldecode(http_build_query($params));

        $answer = file_get_contents($url);

        return json_decode($answer);

    }

    public function glPrepareInfo($user_info) {

        $nickname = $user_info->name;
        $photos = $this->copyAvaWithCopies($user_info->picture);

        return array(
            'nickname' => $nickname,
            'photos' => $photos
        );
    }

    /*
     * gl <--
     */

        /*
     * mr -->
     */

    /*
     * формирует ссылку для перехода и получения кода
     */

    public function mrFirstStep($client_id,$redirect_uri) {

        $state = string_random();
        cmsUser::sessionSet('mr_state', $state);

        $params = array(
            'response_type' => 'code',
            'client_id' => $client_id,
            'redirect_uri'  => $redirect_uri,
            'scope' => 'userinfo',
            'state' => $state
        );
        $oauth_url = 'https://oauth.mail.ru/login?' . urldecode(http_build_query($params));
        $this->redirect($oauth_url);
    }

    /*
     * получает токен токена
     */

    public function mrSecondStep($client_id,$client_secret,$code,$redirect_uri) {

        $headers = array(
            'Content-Type: application/x-www-form-urlencoded',
            'Authorization: Basic '. base64_encode($client_id . ":" . $client_secret)
        );

        $params = array(
            'grant_type' => 'authorization_code',
            'redirect_uri'  => $redirect_uri,
            'code' => $code
        );
        $url = 'https://oauth.mail.ru/token';

        $answer = $this->getCurl($url, $params, $headers);

        return json_decode($answer);

    }

    /*
     * получает данные о пользователе
     * array[0](uid,email,nick,pic_big);
     */

    public function mrThirdStep($token) {

        $params = array(
            'access_token' => $token
        );
        $url = 'https://oauth.mail.ru/userinfo?' . urldecode(http_build_query($params));

        $answer = file_get_contents($url);

        return json_decode($answer);

    }

    public function mrPrepareInfo($user_info) {

        $nickname = $user_info->nickname;
        $photos = $this->copyAvaWithCopies($user_info->image);

        return array(
            'nickname' => $nickname,
            'photos' => $photos
        );
    }

    public function mrCheckState($state) {

        $mr_state = cmsUser::sessionGet('mr_state');
        if($state == $mr_state){
            return true;
        }
        return false;

    }

    /*
     * mr <--
     */

    /*
     * tm -->
     */

    public function tmPrepareInfo($data) {

        cmsUser::sessionSet('zauth:soc', 'tm');
        cmsUser::sessionSet('zauth:socid', $data['id']);
        cmsUser::sessionSet('zauth:name', !empty($data['username']) ? $data['username'] : $this->makeNickname($data['first_name'],$data['last_name']));
        cmsUser::sessionSet('zauth:photo', empty($data['photo_url']) ? false : $data['photo_url']);

    }

    public function getTmData() {
        $data = [];
        $data['id'] = $this->request->get('id','');
        $data['first_name'] = $this->request->get('first_name','');
        $data['last_name'] = $this->request->get('last_name','');
        $data['username'] = $this->request->get('username','');
        $data['photo_url'] = $this->request->get('photo_url','');
        $data['auth_date'] = $this->request->get('auth_date','');
        $data['hash'] = $this->request->get('hash','');
        return $data;
    }


    /*
     * tm <--
     */

}
