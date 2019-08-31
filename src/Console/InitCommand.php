<?php

namespace Ijiabao\Laravel\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class InitCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    //protected $signature = 'command:name';

    // 使用扩展Arguments 和 Options 定义命令行
    protected $name = "ijiabao:init";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'helper for laravel init';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 定义参数
     */
    protected function getArguments()
    {
        return [
            ['action', InputArgument::IS_ARRAY, 'the action']
        ];
    }

    /**
     * 定义选项参数
     */
    protected function getOptions(){
        return [
            ['lang', 'L', InputOption::VALUE_OPTIONAL, 'if has this option, default is zh-CN.', ''],
            ['usermodel', null, InputOption::VALUE_NONE, 'set user model into dir: App/Models/User'],
        ];
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //
        $this->checkSetLang();
		$this->checkUserModel();
    }

    /**
     * 设置语言
     */
    protected function checkSetLang(){
        $lang = $this->option('lang');
        if(is_null($lang)){ // 仅有--lang, 没指定=, 否则为空字符
            $lang = 'zh-CN';
        }
        if(!$lang){
            return false;
        }
        $dir = base_path('vendor/caouecs/laravel-lang/src/'.$lang);
        if(!is_dir($dir)){
            $this->info('语言目录不存在, 或运行 `composer require caouecs/laravel-lang`');
        }
        $destDir = resource_path('lang/'.$lang);
        @mkdir($destDir, 0777, true);
        foreach(scandir($dir) as $f){
            if($f=='.' || $f=='..') continue;
            copy($dir.'/'.$f, $destDir.'/'.$f);
            $this->info('已复制：'. $destDir.'/'.$f);
        }

        // 修改 config/app.php
        $f = config_path('app.php');
        $txt = file_get_contents($f);
        $txt = preg_replace_callback("/^(\s*)'locale'\s*\=\>(.*?)\,/m", function($mat) use($lang){
            return $mat[1]."'locale' => '$lang',";
        }, $txt);
        file_put_contents($f, $txt);
        $test = require($f);
        if($test['locale'] != $lang){
            $this->warn("需要在 `config/app.php` 里设置 'locale' => '$lang'");
        }
    }

    /**
     * 修改User Model 目录
     */
    protected function checkUserModel(){
        $usermodel = $this->option('usermodel');
        if(!$usermodel){
            return false;
        }

        if(class_exists('\App\Models\User')){
            $this->info('App\Models\User 已存在!');
            return true;
        }

        $this->info('修改 User Model 目录 => App\Models\User');

        $orig = app_path('User.php');
        
        @mkdir(app_path('Models'), 0777, true);

        try {
            // set namespace
            $txt = file_get_contents($orig);
            $txt = preg_replace('/^namespace\\s+App;/m', 'namespace App\Models;', $txt);
            file_put_contents(app_path('Models/User.php'), $txt);
    
            // config/auth.php
            $f = config_path('auth.php');
            $txt = file_get_contents($f);
            $txt = str_replace('App\User::class', 'App\Models\User::class', $txt);
            file_put_contents($f, $txt);
    
            // app/Http/Controllers/Auth/RegisterController.php
            $f = app_path('Http/Controllers/Auth/RegisterController.php');
            $txt = file_get_contents($f);

            $txt = preg_replace('/^use App\\\\User;/m', 'use App\Models\User;', $txt);
            file_put_contents($f, $txt);
    
            // database/factories/UserFactory.php
            $f = base_path('database/factories/UserFactory.php');
            $txt = file_get_contents($f);
            $txt = str_replace('App\User::class', 'App\Models\User::class', $txt);
            file_put_contents($f, $txt);
            
            // 重命名 app/User.php
            rename($orig, $orig.'.orig');
        }
        catch(\Exception $e){
            $this->error('修改文件失败: ' . $e->getMessage());
            return false;
        }

        $this->info('已修改，请检查以下文件中的 namespace 是否正确:');
        $this->info("\t". 'app/Models/User.php');
        $this->info("\t". 'config/auth.php');
        $this->info("\t". 'app/Http/Controllers/Auth/RegisterController.php');
        $this->info("\t". 'database/factories/UserFactory.php');
        return true;
    }
}
