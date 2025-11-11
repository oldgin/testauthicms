<?php
class actionZauthResubmit extends cmsAction {

    public function run(){

        if (empty($this->options['verify'])) {
            cmsCore::error404();
        }

        if ($this->cms_user->is_logged && !$this->cms_user->is_admin) {
            $this->redirectToHome();
        }

        //тут случайно
        if (!cmsUser::isSessionSet('zauth:socid')) {
            $this->informUserAndHome();
        }

        //мыло не указал
        if (!cmsUser::isSessionSet('zauth:email')) {
            $this->redirectToAction('finish');
        }

        //дотикал токен?
        $email = cmsUser::sessionGet('zauth:email');
        $reg_user = $this->model->getUserTokenByEmail($email);
        if ($reg_user) {
            $reg_user['resubmit_extime'] = modelAuth::RESUBMIT_TIME - (time() - strtotime($reg_user['date_token']));
            if ($reg_user['resubmit_extime'] < 0) {
                $reg_user['resubmit_extime'] = 0;
            }
        }

        if($reg_user['resubmit_extime'] > 0){
            return cmsCore::errorForbidden();
        }

        //обновим дату токена
        $this->model->update('zusers_tokens',$reg_user['id'], array(
            'date_token' => null
        ));

        $this->generateToken($email, cmsUser::sessionGet('zauth:soc'), cmsUser::sessionGet('zauth:socid'));

        $this->redirectToAction('verify');

    }

}
