<?php
declare(strict_types=1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;

/**
 * 逐梦记工 安装命令
 * 用法：php think install [--force]
 * 自动适配当前默认连接驱动（mysql / sqlite），创建全部数据表并写入初始数据
 */
class Install extends Command
{
    protected function configure(): void
    {
        $this->setName('install')
            ->addOption('force')
            ->setDescription('安装逐梦记工：创建数据表并初始化超级管理员等基础数据');
    }

    protected function execute(Input $input, Output $output): void
    {
        $force = (bool) $input->getOption('force');
        $driver = (string) Db::connect()->getConfig('type');
        $isMysql = str_contains($driver, 'mysql');
        $output->writeln('<info>当前数据库驱动：' . $driver . '</info>');

        $tables = $isMysql ? $this->mysqlDdl() : $this->sqliteDdl();

        foreach ($tables as $table => $ddl) {
            $exists = Db::query($isMysql
                ? "SHOW TABLES LIKE '{$table}'"
                : "SELECT name FROM sqlite_master WHERE type='table' AND name='{$table}'");
            if ($exists) {
                if (!$force) {
                    $output->writeln("<comment>表 {$table} 已存在，跳过（加 --force 可重建）</comment>");
                    continue;
                }
                Db::execute($isMysql ? "DROP TABLE `{$table}`" : "DROP TABLE `{$table}`");
                $output->writeln("<comment>已删除旧表 {$table}</comment>");
            }
            Db::execute($ddl);
            $output->writeln("<info>创建表 {$table} 完成</info>");
        }

        $this->seed($output);
        $output->writeln('<info>========================================</info>');
        $output->writeln('<info>逐梦记工安装完成！</info>');
        $output->writeln('<info>超级管理员账号：1000  密码：zmvip230827</info>');
        $output->writeln('<info>========================================</info>');
    }

    /**
     * 初始数据
     */
    protected function seed(Output $output): void
    {
        // 超级管理员（ID 固定 1000）
        if (!Db::name('users')->where('id', 1000)->find()) {
            Db::name('users')->insert([
                'id'              => 1000,
                'real_name'       => '超级管理员',
                'role'            => 'super',
                'password'        => password_hash('zmvip230827', PASSWORD_DEFAULT),
                'phone'           => '',
                'email'           => '',
                'avatar'          => '',
                'bio'             => '逐梦记工超级管理员',
                'signature'       => '',
                'status'          => 1,
                'last_login_time' => null,
                'last_login_ip'   => '',
                'create_time'     => date('Y-m-d H:i:s'),
                'update_time'     => date('Y-m-d H:i:s'),
            ]);
            $output->writeln('<info>已创建超级管理员（ID:1000）</info>');
        }

        // 系统默认配置
        $configs = [
            ['system', 'site_name', '网站站名', '逐梦记工', 'text'],
            ['system', 'site_url', '网站地址', 'https://zmjg.zmvip.cn', 'text'],
            ['system', 'icp', '备案号', '', 'text'],
            ['system', 'contact', '联系方式', '', 'text'],
            ['system', 'copyright', '版权信息', '逐梦记工 版权所有', 'text'],
            ['email', 'host', 'SMTP服务器', '', 'text'],
            ['email', 'port', 'SMTP端口', '465', 'number'],
            ['email', 'username', '发件邮箱账号', '', 'text'],
            ['email', 'password', '发件邮箱密码/授权码', '', 'text'],
            ['email', 'from_name', '发件人名称', '逐梦记工', 'text'],
            ['sms', 'provider', '短信服务商', '', 'text'],
            ['sms', 'access_key', 'AccessKey', '', 'text'],
            ['sms', 'secret_key', 'SecretKey', '', 'text'],
            ['sms', 'sign_name', '短信签名', '逐梦记工', 'text'],
        ];
        $now = date('Y-m-d H:i:s');
        foreach ($configs as [$group, $name, $title, $value, $type]) {
            if (!Db::name('system_configs')->where(['group' => $group, 'name' => $name])->find()) {
                Db::name('system_configs')->insert([
                    'group'       => $group,
                    'name'        => $name,
                    'title'       => $title,
                    'value'       => $value,
                    'type'        => $type,
                    'create_time' => $now,
                    'update_time' => $now,
                ]);
            }
        }
        $output->writeln('<info>已写入系统默认配置</info>');

        // 默认帮助文章
        if (!Db::name('help_articles')->where('id', 1)->find()) {
            Db::name('help_articles')->insert([
                'id'          => 1,
                'title'       => '欢迎使用逐梦记工帮助中心',
                'category'    => 'web',
                'content'     => "<h3>逐梦记工使用帮助</h3><p>1. 管理员在后台创建账号后，用户使用 用户ID + 初始密码（zmjg+用户ID）登录。</p><p>2. 在「当前项目」中创建项目，每个用户最多同时存在 3 个进行中的项目。</p><p>3. 进入项目内页可按日历添加上班记录、加班时间，并设置日工资与加班工资系数。</p><p>4. 项目结束后点击「结束项目」，项目将转入「历史项目」。</p>",
                'sort'        => 0,
                'status'      => 1,
                'create_time' => $now,
                'update_time' => $now,
            ]);
            $output->writeln('<info>已写入默认帮助文章</info>');
        }
    }

    private function mysqlDdl(): array
    {
        return [
            'users' => "CREATE TABLE `users` (
  `id` int UNSIGNED NOT NULL COMMENT '用户ID（1000起，删除后可复用）',
  `real_name` varchar(50) NOT NULL DEFAULT '' COMMENT '姓名/昵称',
  `role` varchar(10) NOT NULL DEFAULT 'user' COMMENT '角色 super/admin/user',
  `password` varchar(255) NOT NULL DEFAULT '' COMMENT '密码哈希',
  `phone` varchar(30) NOT NULL DEFAULT '' COMMENT '电话',
  `email` varchar(100) NOT NULL DEFAULT '' COMMENT '邮箱',
  `avatar` varchar(255) NOT NULL DEFAULT '' COMMENT '头像',
  `bio` varchar(500) NOT NULL DEFAULT '' COMMENT '简介',
  `signature` varchar(255) NOT NULL DEFAULT '' COMMENT '个性签名',
  `status` tinyint NOT NULL DEFAULT 1 COMMENT '1启用 0停用',
  `last_login_time` datetime NULL COMMENT '最后登录时间',
  `last_login_ip` varchar(45) NOT NULL DEFAULT '' COMMENT '最后登录IP',
  `create_time` datetime NULL,
  `update_time` datetime NULL,
  PRIMARY KEY (`id`),
  KEY `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户表'",

            'projects' => "CREATE TABLE `projects` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int UNSIGNED NOT NULL COMMENT '所属用户ID',
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '项目名称',
  `description` varchar(500) NOT NULL DEFAULT '' COMMENT '项目说明',
  `start_date` date NOT NULL COMMENT '开始日期',
  `end_date` date NULL COMMENT '结束日期（NULL为进行中）',
  `status` tinyint NOT NULL DEFAULT 1 COMMENT '1进行中 0已结束',
  `is_default` tinyint NOT NULL DEFAULT 0 COMMENT '是否默认项目',
  `daily_wage` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '日工资',
  `overtime_coefficient` decimal(4,2) NOT NULL DEFAULT 1.50 COMMENT '加班工资系数',
  `create_time` datetime NULL,
  `update_time` datetime NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_status` (`user_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='项目表'",

            'work_records' => "CREATE TABLE `work_records` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id` bigint UNSIGNED NOT NULL COMMENT '项目ID',
  `user_id` int UNSIGNED NOT NULL COMMENT '用户ID',
  `record_date` date NOT NULL COMMENT '记录日期',
  `work_days` decimal(4,1) NOT NULL DEFAULT 1.0 COMMENT '上班天数（支持0.5天）',
  `overtime_hours` decimal(5,1) NOT NULL DEFAULT 0.0 COMMENT '加班小时',
  `note` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
  `create_time` datetime NULL,
  `update_time` datetime NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_project_date` (`project_id`,`record_date`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='工天记录表'",

            'app_files' => "CREATE TABLE `app_files` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `platform` varchar(10) NOT NULL DEFAULT 'android' COMMENT '平台 android/ios/harmony',
  `version` varchar(50) NOT NULL DEFAULT '' COMMENT '版本号',
  `file_name` varchar(150) NOT NULL DEFAULT '' COMMENT '文件名',
  `file_url` varchar(255) NOT NULL DEFAULT '' COMMENT '下载地址',
  `file_size` varchar(30) NOT NULL DEFAULT '' COMMENT '文件大小',
  `update_note` text COMMENT '更新说明',
  `status` tinyint NOT NULL DEFAULT 1 COMMENT '1上架 0下架',
  `sort` int NOT NULL DEFAULT 0 COMMENT '排序',
  `create_time` datetime NULL,
  `update_time` datetime NULL,
  PRIMARY KEY (`id`),
  KEY `idx_platform` (`platform`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='APP安装包管理表'",

            'system_configs' => "CREATE TABLE `system_configs` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `group` varchar(30) NOT NULL DEFAULT 'system' COMMENT '分组 system/email/sms',
  `name` varchar(50) NOT NULL COMMENT '配置名',
  `title` varchar(100) NOT NULL DEFAULT '' COMMENT '显示名',
  `value` text COMMENT '配置值',
  `type` varchar(20) NOT NULL DEFAULT 'text' COMMENT '字段类型',
  `create_time` datetime NULL,
  `update_time` datetime NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_group_name` (`group`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='系统配置表'",

            'help_articles' => "CREATE TABLE `help_articles` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` varchar(150) NOT NULL DEFAULT '' COMMENT '标题',
  `category` varchar(30) NOT NULL DEFAULT 'web' COMMENT '分类 web/android/ios/harmony',
  `content` text COMMENT '内容',
  `sort` int NOT NULL DEFAULT 0 COMMENT '排序',
  `status` tinyint NOT NULL DEFAULT 1 COMMENT '1显示 0隐藏',
  `create_time` datetime NULL,
  `update_time` datetime NULL,
  PRIMARY KEY (`id`),
  KEY `idx_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='帮助文章表'",
        ];
    }

    private function sqliteDdl(): array
    {
        return [
            'users' => "CREATE TABLE `users` (
  `id` INTEGER NOT NULL,
  `real_name` TEXT NOT NULL DEFAULT '',
  `role` TEXT NOT NULL DEFAULT 'user',
  `password` TEXT NOT NULL DEFAULT '',
  `phone` TEXT NOT NULL DEFAULT '',
  `email` TEXT NOT NULL DEFAULT '',
  `avatar` TEXT NOT NULL DEFAULT '',
  `bio` TEXT NOT NULL DEFAULT '',
  `signature` TEXT NOT NULL DEFAULT '',
  `status` INTEGER NOT NULL DEFAULT 1,
  `last_login_time` TEXT,
  `last_login_ip` TEXT NOT NULL DEFAULT '',
  `create_time` TEXT,
  `update_time` TEXT,
  PRIMARY KEY (`id`)
)",

            'projects' => "CREATE TABLE `projects` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `user_id` INTEGER NOT NULL,
  `name` TEXT NOT NULL DEFAULT '',
  `description` TEXT NOT NULL DEFAULT '',
  `start_date` TEXT NOT NULL,
  `end_date` TEXT,
  `status` INTEGER NOT NULL DEFAULT 1,
  `is_default` INTEGER NOT NULL DEFAULT 0,
  `daily_wage` REAL NOT NULL DEFAULT 0,
  `overtime_coefficient` REAL NOT NULL DEFAULT 1.5,
  `create_time` TEXT,
  `update_time` TEXT
)",

            'work_records' => "CREATE TABLE `work_records` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `project_id` INTEGER NOT NULL,
  `user_id` INTEGER NOT NULL,
  `record_date` TEXT NOT NULL,
  `work_days` REAL NOT NULL DEFAULT 1,
  `overtime_hours` REAL NOT NULL DEFAULT 0,
  `note` TEXT NOT NULL DEFAULT '',
  `create_time` TEXT,
  `update_time` TEXT,
  UNIQUE (`project_id`,`record_date`)
)",

            'app_files' => "CREATE TABLE `app_files` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `platform` TEXT NOT NULL DEFAULT 'android',
  `version` TEXT NOT NULL DEFAULT '',
  `file_name` TEXT NOT NULL DEFAULT '',
  `file_url` TEXT NOT NULL DEFAULT '',
  `file_size` TEXT NOT NULL DEFAULT '',
  `update_note` TEXT,
  `status` INTEGER NOT NULL DEFAULT 1,
  `sort` INTEGER NOT NULL DEFAULT 0,
  `create_time` TEXT,
  `update_time` TEXT
)",

            'system_configs' => "CREATE TABLE `system_configs` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `group` TEXT NOT NULL DEFAULT 'system',
  `name` TEXT NOT NULL,
  `title` TEXT NOT NULL DEFAULT '',
  `value` TEXT,
  `type` TEXT NOT NULL DEFAULT 'text',
  `create_time` TEXT,
  `update_time` TEXT,
  UNIQUE (`group`,`name`)
)",

            'help_articles' => "CREATE TABLE `help_articles` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `title` TEXT NOT NULL DEFAULT '',
  `category` TEXT NOT NULL DEFAULT 'web',
  `content` TEXT,
  `sort` INTEGER NOT NULL DEFAULT 0,
  `status` INTEGER NOT NULL DEFAULT 1,
  `create_time` TEXT,
  `update_time` TEXT
)",
        ];
    }
}
