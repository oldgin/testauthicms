<?php
$this->addTplCSS('controllers/zauth/widgets/zauth/zauth');
$this->renderControllerChild('zauth','list_small',[
    'links' => $links,
    'size' => $size
]);