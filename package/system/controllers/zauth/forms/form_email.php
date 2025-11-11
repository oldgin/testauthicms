<?php
class formZauthEmail extends cmsForm {
    public function init()
    {

        return array(
            array(
                'type' => 'fieldset',
                'childs' => array(

			                        new fieldString('email', array(
                        'title' => 'Укажите ваш е-майл',
                        'rules' => array(
                            array('required'),
                            array('email')
                        )
                    ))
                ),
            ),

			            );

		    }
}
