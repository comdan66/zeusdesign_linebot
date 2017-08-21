<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2016 OA Wu Design
 */

class Migration_Add_log_texts extends CI_Migration {
  public function up () {
    $this->db->query (
      "CREATE TABLE `log_texts` (
        `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `log_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT 'Line Bot Log ID',
        
        `text` text NOT NULL COMMENT '訊息內容',
        PRIMARY KEY (`id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;"
    );
  }
  public function down () {
    $this->db->query (
      "DROP TABLE `log_texts`;"
    );
  }
}