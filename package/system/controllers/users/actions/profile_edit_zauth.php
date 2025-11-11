<?php

class actionUsersProfileEditZauth extends cmsAction {

    public $lock_explicit_call = true;

    public function run($profile){

        // проверяем наличие доступа
        if (!$this->is_own_profile && !$this->cms_user->is_admin) { cmsCore::error404(); }

        $zauth_controller = cmsCore::getController('zauth');

        //а включен ли компонент
        if(!$zauth_controller->isEnabled()){
            return $this->cms_template->render('profile_edit_zauth', array(
                'text' => LANG_ZAUTH_OFFLINE
            ));
        }

        //получим список уже активированных соц. сетей
        $zauths = $zauth_controller->model->getUserZauths($profile['id']);
        $zauths_socs_keys = $zauths ? array_column($zauths, 'soc') : [];

        //список имен всех доступных соц. сетей
        $providers = $zauth_controller->getProviders();

        //получим список всех соц. сетей с пометкой включенных
        $links = $zauth_controller->getlinks();

        //и список доступных, за вычетом уже добавленных
        $available_links = [];
        foreach ($links as $soc => $link){
            if(!empty($link) && !in_array($soc, $zauths_socs_keys)){
                $available_links[$soc] = $link;
            }
        }

        $liststyle = empty($zauth_controller->options['type']) ? 'list' : $zauth_controller->options['type'];
        $size = $zauth_controller->getOption('size');

        return $this->cms_template->render('profile_edit_zauth', array(
            'id'      => $profile['id'],
            'profile' => $profile,
            'zauths' => $zauths,
            'available_links' => $available_links,
            'providers' => $providers,
            'liststyle' => $liststyle,
            'size' => $size
        ));

    }

}
