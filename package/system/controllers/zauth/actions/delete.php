<?php

class actionZauthDelete extends cmsAction {

    /**
     * экшн используется для удаления привязки соц. сети
     */
    public function run($id) {

        $csrf_token = $this->request->get('csrf_token', '');
        if (!cmsForm::validateCSRFToken($csrf_token)) {
            return cmsCore::error404();
        }

        $zauth = $this->model->getZauth($id);
        if(!$zauth){
            return cmsCore::error404();
        }

        if(cmsUser::get('id') != $zauth['user_id']){
            return cmsCore::error404();
        }

        //ну если сюда добрались, значит все ок, удаляем
        $this->model->deleteZauth($id);

        return $this->redirectBack();

    }

}
