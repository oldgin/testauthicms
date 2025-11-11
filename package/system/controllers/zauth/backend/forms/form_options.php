<?php

class formZauthOptions extends cmsForm {

    public function init() {

        return array(
            array(
                'type' => 'fieldset',
                'title' => LANG_OPTIONS,
                'childs' => array(
                    new fieldListGroups('groups', array(
                        'title' => 'Группы пользователя',
                        'hint' => 'выберите группы, в которые будет помещен пользователь при регистрации',
                        'show_all' => false,
			'default' => array(3)
                    )),
                    new fieldCheckbox('notice_admin', array(
                        'title' => 'Уведомлять об ошибках',
                        'hint' => 'если отмечено, администратор сайта будет получать уведомления об ошибках в процессе регистрации пользователей'
                    )),
                    new fieldList('type',[
                        'title' => 'Шаблон вывода',
                        'hint' => 'выберите шаблон, с помощью которого выводить кнопки в виджете авторизации и на странице входа',
                        'generator' => function(){
                            $files = cmsTemplate::getInstance()->getAvailableTemplatesFiles('controllers/zauth', 'list*.tpl.php');
                            if (!$files) { return []; }
                            return $files;
                        }
                    ]),
                    new fieldList('size',[
                        'title' => 'Размер кнопок',
                        'hint' => 'выберите размер из списка, отступы и др. будут подобраны автоматически',
                        'items' => [''=>''] + array_combine(range(12, 60),range(12, 60))
                    ]),
                    new fieldCheckbox('verify', array(
                        'title' => 'НЕ требовать от пользователя уникальный email',
                        'hint' => 'если отмечено, на этапе указания email пользователь сможет указать email, который уже используется на сайте<br>После подтверждения по почте использованный для входа аккаунт соц. сети будет привязан к пользователю, чей email был указан при регистрации'
                    )),
                    new fieldNumber('verify_exp', array(
                        'title'   => 'Сколько минут ждать подтверждения',
                        'default' => 15,
                        'rules' => array(
                            array('required'),
                            array('min', 1)
                        )
                    ))
                )),
            array(
                'type' => 'fieldset',
                'title' => 'Настройки вконтакте',
                'childs' => array(
                    new fieldCheckbox('vk_on', array(
                        'title' => 'Разрешить регистрацию с помощью вконтакте'
                            )),
                    new fieldString('vk_client_id', array(
                        'title' => 'ID приложения',
                        'visible_depend' => array('vk_on' => array('show' => array('1')))
                            )),
                    new fieldString('vk_client_secret', array(
                        'title' => 'Защищённый ключ приложения',
                        'visible_depend' => array('vk_on' => array('show' => array('1')))
                            ))
                )),
            array(
                'type' => 'fieldset',
                'title' => 'Настройки одноклассники',
                'childs' => array(
                    new fieldCheckbox('ok_on', array(
                        'title' => 'Разрешить регистрацию с помощью одноклассники'
                            )),
                    new fieldString('ok_client_id', array(
                        'title' => 'Application ID',
                        'visible_depend' => array('ok_on' => array('show' => array('1')))
                            )),
                    new fieldString('ok_client_public', array(
                        'title' => 'Публичный ключ приложения',
                        'visible_depend' => array('ok_on' => array('show' => array('1')))
                            )),
                    new fieldString('ok_client_secret', array(
                        'title' => 'Секретный ключ приложения',
                        'visible_depend' => array('ok_on' => array('show' => array('1')))
                            ))
                )),
            array(
                'type' => 'fieldset',
                'title' => 'Настройки фейсбук',
                'childs' => array(
                    new fieldCheckbox('fb_on', array(
                        'title' => 'Разрешить регистрацию с помощью фейсбук'
                            )),
                    new fieldString('fb_client_id', array(
                        'title' => 'ID приложения',
                        'visible_depend' => array('fb_on' => array('show' => array('1')))
                            )),
                    new fieldString('fb_client_secret', array(
                        'title' => 'Секретный ключ приложения',
                        'visible_depend' => array('fb_on' => array('show' => array('1')))
                            ))
                )),
            array(
                'type' => 'fieldset',
                'title' => 'Настройки Яндекс',
                'childs' => array(
                    new fieldCheckbox('ya_on', array(
                        'title' => 'Разрешить регистрацию с помощью яндекс'
                            )),
                    new fieldString('ya_client_id', array(
                        'title' => 'ID приложения',
                        'visible_depend' => array('ya_on' => array('show' => array('1')))
                            ))
                )),
            array(
                'type' => 'fieldset',
                'title' => 'Настройки Гугл',
                'childs' => array(
                    new fieldCheckbox('gl_on', array(
                        'title' => 'Разрешить регистрацию с помощью гугл'
                            )),
                    new fieldString('gl_client_id', array(
                        'title' => 'ID клиента',
                        'visible_depend' => array('gl_on' => array('show' => array('1')))
                            )),
                    new fieldString('gl_client_secret', array(
                        'title' => 'Секретный ключ клиента',
                        'visible_depend' => array('gl_on' => array('show' => array('1')))
                            ))
                )),
            array(
                'type' => 'fieldset',
                'title' => 'Настройки Майл.ру',
                'childs' => array(
                    new fieldCheckbox('mr_on', array(
                        'title' => 'Разрешить регистрацию с помощью майл.ру'
                            )),
                    new fieldString('mr_client_id', array(
                        'title' => 'ID сайта',
                        'visible_depend' => array('mr_on' => array('show' => array('1')))
                            )),
                    new fieldString('mr_client_secret', array(
                        'title' => 'Секретный ключ',
                        'visible_depend' => array('mr_on' => array('show' => array('1')))
                            ))
                )),
            array(
                'type' => 'fieldset',
                'title' => 'Настройки Телеграм',
                'childs' => array(
                    new fieldCheckbox('tm_on', array(
                        'title' => 'Разрешить регистрацию с помощью телеграм'
                            )),
                    new fieldString('tm_bot_name', array(
                        'title' => 'Имя бота',
                        'hint' => 'которое вы задавали боту в процессе его регистрации у Botfather',
                        'visible_depend' => array('tm_on' => array('show' => array('1')))
                            )),
                    new fieldString('tm_bot_token', array(
                        'title' => 'Токен бота',
                        'visible_depend' => array('tm_on' => array('show' => array('1')))
                            ))
                ))
        );
    }

}
