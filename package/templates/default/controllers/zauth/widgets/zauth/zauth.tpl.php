<?php
$this->addTplCSS('controllers/dbcauth/widgets/dbcauth/dbcauth');
$this->renderControllerChild('dbcauth','list',[
    'links' => $links,
    'size' => $size
]);
