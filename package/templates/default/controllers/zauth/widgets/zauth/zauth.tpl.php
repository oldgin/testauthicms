<?php
$this->addTplCSS('controllers/zauth/widgets/zauth/zauth');
$this->renderControllerChild('zauth','list',[
    'links' => $links,
    'size' => $size
]);