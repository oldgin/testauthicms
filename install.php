<?php

function install_package() {

    $core = cmsCore::getInstance();

    //установка компонента
    if (!$core->db->getRowsCount('controllers', "name = 'dbcauth'")) {
        $core->db->query("INSERT INTO `{#}controllers` (`title`, `name`, `slug`, `is_enabled`, `options`, `author`, `url`, `version`, `is_backend`, `is_external`, `files`, `addon_id`) VALUES ('DBCАвторизация', 'dbcauth', NULL, 1, '', 'Convero', 'http://convero.ru', '1.1.0', 1, NULL, NULL, NULL);");
    }

    if (!$core->db->getRowsCount('widgets', "controller = 'dbcauth' AND name = 'dbcauth'")) {
        $core->db->query("INSERT INTO `{#}widgets` (`controller`, `name`, `title`, `author`, `url`, `version`, `is_external`, `files`, `addon_id`) VALUES ('dbcauth', 'dbcauth', 'Zавторизация', 'Convero', 'http://convero.ru', '1.1.1', 1, NULL, NULL);
");
    }

    //основная таблица
    $core->db->query("CREATE TABLE IF NOT EXISTS `{#}dbcusers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `soc` varchar(20) NOT NULL,
  `soc_id` bigint(18) UNSIGNED NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

    //таблица токенов
    $core->db->query("CREATE TABLE IF NOT EXISTS `{#}dbcusers_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(100) NOT NULL,
  `soc` varchar(20) NOT NULL,
  `soc_id` bigint(18) UNSIGNED NOT NULL,
  `pass_token` varchar(32) NOT NULL,
  `date_token` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

    //обновление
    $core->db->query("UPDATE `{#}controllers` SET `version` = '1.1.0' WHERE `name` = 'dbcauth';");
    $core->db->query("UPDATE  `{#}widgets` SET  `version` =  '1.1.0' WHERE  `{#}widgets`.`controller` = 'dbcauth' AND `{#}widgets`.`name` = 'dbcauth';");

    //1.0.5
    $core->db->query("ALTER TABLE `{#}dbcusers` CHANGE `soc_id` `soc_id` BIGINT(18) UNSIGNED NOT NULL;");

	//1.0.6
	$core->db->dropIndex('dbcusers', 'user_id');


    return true;
}
