<?php
    $this->setPageTitle('Последний шаг регистрации');
    $this->addBreadcrumb('Последний шаг регистрации');

?>

<p>Остался последний шаг. Укажите ваш е-майл</p>
    
<div id="form">
    <?php
    $this->renderForm($form, $item, array(
        'method' => 'post',
        'action' => '',
        'submit' => array('title' => 'Зарегистрироваться')
            ), $errors);
    ?>
</div>
