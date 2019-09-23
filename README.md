# Laravel Any

Laravel 开发时常用的轮子



* 设置数据库默认utf8mb4

* 设置字符串索引长度191

* prepend_view_path() 函数, 动态设置视图目录

* 使用route group时,自动优先使用 `prefix` 做为优先视图目录

  



## 快速同步数据库

```bash
# 导出(备份)数据库(默认存在storage/dbdump下)
php artisan ijiabao:dbdump export
# 导入, (会自动备份当前)
php artisan ijiabao:dbdump import
# 备份文件设为版本控制, 用于项目同步
php artisan ijiabao:dbdump gitset
```



## 集成组件

### ide helper

```bash
# composer require barryvdh/laravel-ide-helper
php artisan ide-helper:generate
php artisan ide-helper:model
```

### 语言包

```bash
# composer require caouecs/laravel-lang
# 复制中文包到项目 (xh_CN)
php artisan ijiabao:init -L
# 其它语言
php artisan ijiabao:init --lang=zh-TW
```



