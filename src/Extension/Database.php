<?php

namespace Botify4\Extension;

class Database extends AbstractExtension
{
  private $db;

  public function connect($config = false)
  {
    if (!$config) {
      $config = $this->bot->config;
    }

    $driver = mb_strtolower($config['database.driver']);

    if (!$driver) {
      return false;
    }

    $factory = new \Database\Connectors\ConnectionFactory();

    if ($driver == 'sqlite') {
        $db = $factory->make([
            'driver'    => 'sqlite',
            'database'  => $config['database.path'],
        ]);
    }

    if ($driver == 'mysql') {
        $db = $factory->make([
            'driver'    => 'mysql',
            'host'      => $config['database.host'],
            'database'  => $config['database.database'],
            'username'  => $config['database.username'],
            'password'  => $config['database.password'],
            'charset'   => $config['database.charset'] ?? 'utf8',
            'collation' => $config['database.collation'] ?? 'utf8_unicode_ci',
            'lazy'      => $config['database.lazy'] ?? true,
        ]);
    }

    $this->db = $db;

    return $this->db;
  }

  public function initTables($db = false)
  {
    if (!$db) {
      $db = $this->db;
    }

    $user_sql = "CREATE TABLE IF NOT EXISTS `users` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `user_id` bigint(20) NOT NULL,
      `first_message` int(11) DEFAULT NULL,
      `last_message` int(11) DEFAULT NULL,
      `full_name` text DEFAULT NULL,
      `first_name` text DEFAULT NULL,
      `last_name` text DEFAULT NULL,
      `username` text DEFAULT NULL,
      `nickname` text DEFAULT NULL,
      `user_icon` text DEFAULT NULL,
      `lang` text DEFAULT NULL,
      `photo` text DEFAULT NULL,
      `is_banned` tinyint(4) DEFAULT NULL,
      `ban_comment` text DEFAULT NULL,
      `ban_from` int(11) DEFAULT NULL,
      `ban_to` int(11) DEFAULT NULL,
      `state_name` text DEFAULT NULL,
      `state_data` mediumtext DEFAULT NULL,
      `is_active` tinyint(4) DEFAULT NULL,
      `bot_version` text DEFAULT NULL,
      `note` text DEFAULT NULL,
      PRIMARY KEY (`id`)
    );";

    $stats_new_users_sql = "CREATE TABLE IF NOT EXISTS `stats_new_users` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `date` int(11) DEFAULT NULL,
      `count` int(11) NOT NULL,
      PRIMARY KEY (`id`)
    );";

    $stats_messages_sql = "CREATE TABLE IF NOT EXISTS `stats_messages` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `date` int(11) DEFAULT NULL,
      `count` int(11) NOT NULL,
      PRIMARY KEY (`id`)
    );";

    $messages_sql = "CREATE TABLE IF NOT EXISTS `messages` (
      `id` bigint(20) NOT NULL AUTO_INCREMENT,
      `date` int(11) DEFAULT NULL,
      `user_id` bigint(20) DEFAULT NULL,
      `user` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
      `value` mediumtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
      PRIMARY KEY (`id`)
    );";

    $users = $db->query($user_sql)->execute();
    $stats_new_users = $db->query($stats_new_users_sql)->execute();
    $stats_messages = $db->query($stats_messages_sql)->execute();
    $messages = $db->query($messages_sql)->execute();
  }
}
