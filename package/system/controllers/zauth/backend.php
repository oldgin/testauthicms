<?php

class backendZauth extends cmsBackend {

    public $useDefaultOptionsAction = true;
    protected $useOptions = true;

    public function actionIndex() {

        $this->redirectToAction('zusers');
    }

        public function getBackendMenu() {

        return array(
            array(
                'title' => LANG_OPTIONS,
                'url' => href_to($this->root_url, 'options')
            ),
            array(
                'title' => 'Пользователи',
                'url' => href_to($this->root_url, 'zusers')
            )
        );
    }

}
