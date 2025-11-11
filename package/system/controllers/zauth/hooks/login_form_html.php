<?php

class onZauthLoginFormHtml extends cmsAction {

    public function run(){

        $links = $this->getlinks();
        $template = cmsTemplate::getInstance();
        $template->addCSSFromContext($template->getTplFilePath('controllers/zauth/widgets/zauth/zauth.css',false));
        if($this->cms_core->request->isAjax()){
            ob_start();
            ?>
            <script>
                setTimeout(function(){
                    /* так как модалка неправильно считает размеры окна, пока не прогрузятся иконки */
                    icms.modal.resize();
                },2000);
            </script>
            <?php
            $template->addBottom(ob_get_clean());
        }
        ob_start();
        $liststyle = empty($this->options['type']) ? 'list' : $this->options['type'];
        $size = $this->getOption('size');

                $template->renderControllerChild('zauth',$liststyle,[
            'links' => $links,
            'size' => $size
        ]);

        return ob_get_clean();

    }

}
