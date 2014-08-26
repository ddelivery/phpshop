
DROP TABLE IF EXISTS `ddelivery_module_system`;
CREATE TABLE IF NOT EXISTS `ddelivery_module_system` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(64) NOT NULL DEFAULT '',
  `rezhim` varchar(64) NOT NULL DEFAULT '',
  `declared` varchar(64) NOT NULL DEFAULT '',
  `width` varchar(64) NOT NULL DEFAULT '',
  `height` varchar(64) NOT NULL DEFAULT '',
  `api` varchar(120) NOT NULL,
  `length` varchar(64) NOT NULL,
  `weight` varchar(64) NOT NULL,
  `payment` varchar(64) NOT NULL,
  `status` varchar(64) NOT NULL,
  `famile` varchar(64) NOT NULL,
  `name` varchar(64) NOT NULL,
  `def_width` varchar(64) NOT NULL,
  `def_lenght` varchar(64) NOT NULL,
  `def_height` varchar(64) NOT NULL,
  `def_weight` varchar(64) NOT NULL,
  `pvz_companies` varchar(264) NOT NULL,
  `cur_companies` varchar(264) NOT NULL,
  `from1` varchar(64) NOT NULL,
  `to1` varchar(64) NOT NULL,
  `method1` varchar(64) NOT NULL,
  `from2` varchar(64) NOT NULL,
  `to2` varchar(64) NOT NULL,
  `method2` varchar(64) NOT NULL,
  `from3` varchar(64) NOT NULL,
  `to3` varchar(64) NOT NULL,
  `method3` varchar(64) NOT NULL,
  `okrugl` varchar(64) NOT NULL,
  `shag` varchar(64) NOT NULL,
  `zabor` varchar(64) NOT NULL,
  `city1` varchar(64) NOT NULL,
  `curprice1` varchar(64) NOT NULL,
  `city2` varchar(64) NOT NULL,
  `curprice2` varchar(64) NOT NULL,
  `city3` varchar(64) NOT NULL,
  `curprice3` varchar(64) NOT NULL,
  `custom_point` text NOT NULL,
  `methodval1` varchar(64) NOT NULL,
  `methodval2` varchar(64) NOT NULL,
  `methodval3` varchar(64) NOT NULL,
  `delivery_id` varchar(64) NOT NULL,
  `ros_price` varchar(64) NOT NULL,
  `ros_duiring` varchar(64) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=cp1251 ;


INSERT INTO `ddelivery_module_system` (`id`, `type`, `rezhim`, `declared`, `width`, `height`, `api`, `length`, `weight`, `payment`, `status`, `famile`, `name`, `def_width`, `def_lenght`, `def_height`, `def_weight`) VALUES(1, '0', '0', '100', 'option1', 'option3', '852af44bafef22e96d8277f3227f0998', 'option2', 'weight', '2', '23', 'famile', 'name', '10', '11', '10', '1');

DROP TABLE IF EXISTS `ddelivery_module_cache`;


CREATE TABLE `ddelivery_module_cache` (
  `id`  int NOT NULL,
  `data_container`  MEDIUMTEXT NULL ,
  `expired`  datetime NULL,
  `filter_company` TEXT NULL,
  PRIMARY KEY (`id`),
  INDEX `dd_cache` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=cp1251;

DROP TABLE IF EXISTS `ddelivery_module_orders`;

CREATE TABLE `ddelivery_module_orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `payment_variant` varchar(255) DEFAULT NULL,
  `shop_refnum` int(11) DEFAULT NULL,
  `local_status` varchar(255) DEFAULT NULL,
  `dd_status` int(11) DEFAULT NULL,
  `type` int(11) DEFAULT NULL,
  `to_city` int(11) DEFAULT NULL,
  `point_id` int(11) DEFAULT NULL,
  `date` datetime DEFAULT NULL,
  `ddeliveryorder_id` int(11) DEFAULT NULL,
  `delivery_company` int(11) DEFAULT NULL,
  `order_info` text DEFAULT NULL,
  `cache` text DEFAULT NULL,
  `point` text DEFAULT NULL,
  `add_field1` varchar(255) DEFAULT NULL,
  `add_field2` varchar(255) DEFAULT NULL,
  `add_field3` varchar(255) DEFAULT NULL,
  `cart` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



ALTER TABLE `phpshop_products` ADD `option1` VARCHAR(255) NOT NULL;
ALTER TABLE `phpshop_products` ADD `option2` VARCHAR(255) NOT NULL;
ALTER TABLE `phpshop_products` ADD `option3` VARCHAR(255) NOT NULL;
ALTER TABLE `phpshop_products` ADD `option4` VARCHAR(255) NOT NULL;
ALTER TABLE `phpshop_products` ADD `option5` VARCHAR(255) NOT NULL;

--
-- Структура таблицы `phpshop_modules_iconcat_system`
--

DROP TABLE IF EXISTS `phpshop_modules_productoption_system`;
CREATE TABLE IF NOT EXISTS `phpshop_modules_productoption_system` (
  `id` int(11) NOT NULL auto_increment,
  `option` blob NOT NULL,
  `version` float(2) NOT NULL default '1.0',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251;



INSERT INTO `phpshop_modules_productoption_system` (`id`, `option`, `version`) VALUES
  (1, 0x613a31303a7b733a31333a226f7074696f6e5f315f6e616d65223b733a363a22d8e8f0e8ede0223b733a31353a226f7074696f6e5f315f666f726d6174223b733a343a2274657874223b733a31333a226f7074696f6e5f325f6e616d65223b733a363a22c2fbf1eef2e0223b733a31353a226f7074696f6e5f325f666f726d6174223b733a343a2274657874223b733a31333a226f7074696f6e5f335f6e616d65223b733a353a22c4ebe8ede0223b733a31353a226f7074696f6e5f335f666f726d6174223b733a343a2274657874223b733a31333a226f7074696f6e5f345f6e616d65223b733a303a22223b733a31353a226f7074696f6e5f345f666f726d6174223b733a343a2274657874223b733a31333a226f7074696f6e5f355f6e616d65223b733a303a22223b733a31353a226f7074696f6e5f355f666f726d6174223b733a343a2274657874223b7d, 1);