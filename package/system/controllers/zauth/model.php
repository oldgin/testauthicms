<?php

class modelZauth extends cmsModel {

    //сохраняет пользователя в отдельную таблицу
    public function saveUser($user_id,$soc,$soc_id) {
        $this->insert('zusers', array(
            'user_id' => $user_id,
            'soc' => $soc,
            'soc_id' => $soc_id
        ));
    }

    //получает пользователя на основе е-майл
    public function getUserByEmail($email) {
        $this->filterEqual('email', $email);
        return $this->getItem('{users}');
    }

    //получает опции аватаров
    public function getAvatarOptions() {
        //получаем размеры постера
        $this->filterEqual('name', 'avatar');
        return $this->getFieldFiltered('{users}_fields', 'options');
    }

    //ищет пользователя в таблице зареганых
    public function getUserZauth($soc,$soc_id) {
        $this->filterEqual('soc', $soc)->filterEqual('soc_id', $soc_id);
        return $this->getItem('zusers');
    }

    //ищет токен
    public function getUserByToken($token) {
        return $this->getItemByField('zusers_tokens','pass_token',$token);
    }

    public function getUserTokenByEmail($email) {
        return $this->getItemByField('zusers_tokens','email',$email);
    }

    public function getUserZauths($user_id) {
        return $this->filterEqual('user_id', $user_id)->get('zusers');
    }

    public function getZauth($id) {
        return $this->getItemById('zusers',$id);
    }

    public function deleteZauth($id) {
        return $this->delete('zusers',$id);
    }

}
