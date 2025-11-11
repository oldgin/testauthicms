<?php

class formZauthVerify extends cmsForm {

    public function init($reg_user) {

        return array(

            'basic' => array(
                'type' => 'fieldset',
                'title' => LANG_ZAUTH_VERIFY_EMAILCODE,
                'childs' => array(
                    new fieldString('email_token', array(
                        'suffix' => $reg_user ? '<a id="reg_resubmit" data-resubmit_time="'.$reg_user['resubmit_extime'].'" href="'.href_to('zauth', 'resubmit').'">'.LANG_SEND_AGAIN.'</a><span id="reg_resubmit_timer">'.LANG_SEND_AGAIN_VIA.' <strong></strong></span>' : null,
                        'options'=>array(
                            'min_length'=> 32,
                            'max_length'=> 32
                        ),
                        'rules' => array(
                            array('required')
                        )
                    ))
                )
            )

        );

    }

}
