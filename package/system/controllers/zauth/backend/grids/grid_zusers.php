<?php

function grid_zusers($controller){

    $options = array(
        'is_sortable' => true,
        'is_filter' => false,
        'is_pagination' => true,
        'is_draggable' => false,
        'order_by' => 'id',
        'order_to' => 'desc',
        'show_id' => true
    );

    $columns = array(
        'id' => array(
            'title' => 'id',
            'width' => 30
        ),
        'nickname' => array(
            'title' => 'Никнейм',
            'href' => href_to('admin', 'users', array('edit', '{user_id}')),
        ),
        'soc' => array(
            'title' => 'Соц. сеть',
            'handler' => function ($field, $row){
                $providers = cmsCore::getController('zauth')->getProviders();
                return empty($providers[$field]) ? $field : $providers[$field];
            }
        ),
        'soc_id' => array(
            'title' => 'ID пользователя в соц. сети'
        ),
        'email' => array(
            'title' => 'E-mail'
        ),
    );

    $actions = array(
        array(
            'title'   => 'Удалить привязку',
            'class'   => 'delete',
            'href'    => href_to($controller->root_url, 'zusers',['delete', '{id}']),
            'confirm' => 'Вы уверены? Пользователю придется заново проходить процесс привязки социальной сети к аккаунту',
        ),
    );

    return array(
        'options' => $options,
        'columns' => $columns,
        'actions' => $actions
    );

}

