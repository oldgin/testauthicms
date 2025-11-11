<?php

class onZauthProfileEditMenu extends cmsAction{

    public function run($data) {

        list($menu, $profile) = $data;

        //dump($menu);

        $newmenu = [];
        foreach ($menu as $menuitem){
            $newmenu[] = $menuitem;
            if(!empty($menuitem['url']) && strpos($menuitem['url'], 'notices') !== false){
                $newmenu[] = [
                    'title' => LANG_ZAUTH_PROFILE_MENU,
                    'url' => href_to_profile($profile, ['edit', 'zauth'])
                ];
            }
            //old versions
            elseif(!empty($menuitem['params']) && !empty($menuitem['params'][1]) && ($menuitem['params'][1] == 'notices')){
                $newmenu[] = [
                    'title' => LANG_ZAUTH_PROFILE_MENU,
                    'controller' => 'users',
                    'action' => $profile['id'],
                    'params' => ['edit','zauth']
                ];
            }
        }
        return [$newmenu, $profile];
    }

}
