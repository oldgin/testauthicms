<?php

class onZauthUserDelete extends cmsAction {

    public function run($user){

        $this->model->filterEqual('user_id',$user['id'])->deleteFiltered('zusers');

        return $user;

    }

}
