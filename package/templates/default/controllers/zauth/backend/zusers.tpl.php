<?php

$this->addBreadcrumb('Пользователи');

?>

<p>Пользователи, зарегистрировавшиеся с помощью компонента</p>

<?php

$this->renderGrid($this->href_to('zusers'), $grid);