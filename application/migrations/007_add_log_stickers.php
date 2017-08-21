<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2016 OA Wu Design
 */

class Migration_Add_log_stickers extends CI_Migration {
  public function up () {
    $this->db->query (
      "CREATE TABLE `log_stickers` (
        `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `log_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT 'Line Bot Log ID',
        
        `package_id` varchar(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT 'Package ID',
        `sticker_id` varchar(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT 'Sticker ID',
        PRIMARY KEY (`id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;"
    );
  }
  public function down () {
    $this->db->query (
      "DROP TABLE `log_stickers`;"
    );
  }
}