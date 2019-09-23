<?php

namespace Ijiabao\Laravel\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * 使用`mysql`命令行导入导出数据库, 用于开发期间快速同步数据
 */
class DbDumpCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    //protected $signature = 'command:name';

    // 使用扩展 Arguments 和 Options 定义命令行
    protected $name = "ijiabao:dbdump";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'helper for import/export database';

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
            ['action', InputArgument::REQUIRED, 'export|import|clean|gitset'],
        ];
    }

    /**
     * 定义选项参数
     */
    protected function getOptions(){
        return [
            ['table', 'T', InputOption::VALUE_OPTIONAL|InputOption::VALUE_IS_ARRAY, 'the table names, default all table.'],
            ['file', null, InputOption::VALUE_NONE, 'force import/export with this file'],
            ['no-backup', null, InputOption::VALUE_NONE, 'if import, do not backup current tables'],
            ['revert', null, InputOption::VALUE_OPTIONAL, 'revert last backup file. you can set a number']
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
        $action = $this->argument('action');
        if($action=='export'){
            return $this->doExport();
        }
        else if($action == 'import'){
            return $this->doImport();
        }
        else if($action=='clean'){
            return $this->doClean();
        }
        else if($action=='gitset'){
            $dir = $this->getSaveDir();
            @mkdir($dir, 0777, true);
            $content = "!.gitignore\n*.bakup\n";
            file_put_contents($dir.'/.gitignore', $content);
			$this->info("created .gitignore file");
        }
    }

    /**
     * 导出数据库
     */
    protected function doExport($filename=''){
        $cmd = $this->buildConnCmd('mysqldump');
        if(!$cmd){
            return false;
        }
        $file = $this->getSaveFile($filename);
        @mkdir(dirname($file), 0777, true);

        $this->info('正在备份...');
        @exec("{$cmd} > \"{$file}\"", $output, $ret);

        if($ret===0){
            $this->info("成功备份: [$file]");
        }
        else {
            $this->error('备份失败！');
        }
        return ($ret===0);
    }

    /**
     * 导入数据库
     */
    protected function doImport(){
        $file = $this->getSaveFile();
        if(!is_file($file)){
            $this->error("文件不存在: [$file]");
            return false;
        }

        // 备份先
        $bakup = 'dbdump-'.date('YmdHis').'.sql.bakup';
        if(!$this->doExport($bakup)){ 
            return false;
        }

        $cmd = $this->buildConnCmd('mysql');
        if(!$cmd){
            return false;
        }

        $this->info("正在导入..");
        @exec("{$cmd} < \"{$file}\"", $output, $ret);

        if($ret===0){
            $this->info("导入成功！");
        }
        else{
            $this->error("导入失败！");
        }
        return ($ret===0);
    }

    /**
     * 获取保存目录
     */
    protected function getSaveDir(){
        $dir = config('ijiabao.dbdump.save_dir', storage_path('dbdump'));
        return rtrim($dir, '/\\');
    }

    /**
     * 生成保存目录下的文件
     */
    protected function getSaveFile($filename = null){
        $filepath = $this->getSaveDir() . '/'. ($filename ? $filename : 'all.sql');
		if(DIRECTORY_SEPARATOR == "\\"){
			return str_replace('/', '\\', $filepath);
		}
		return $filepath;
    }

    /**
     * 生成命令行
     */
    protected function buildConnCmd($bin){
        $dir = rtrim(config('ijiabao.dbdump.bin_dir', ''), '/\\');
        if($dir){
            $bin = $dir.'/'.$bin;
        }

        @exec($bin.' --version', $output, $ret);
        if($ret !== 0){
            $this->error("路径不存在: [$bin]");
            return false;
        }

        $this->info($output[0]);

        $cfg = \DB::getConfig();
        if($cfg['unix_socket']){
            return "{$bin} -S {$cfg['unix_socket']}";
        }

        $cmd = "";
        $args = [
            'username'=>'user',
            'password'=>'password',
            'host'=>'host',
            'port'=>'port'
        ];
        foreach($args as $k=>$v){
            if($cfg[$k]){
                $cmd .= " --{$v}={$cfg[$k]}";
            }
        }
        return "{$bin} {$cmd} {$cfg['database']}";
    }

    /**
     * 删除备份文件
     */
    protected function doClean(){
        $status = true;
        $glob = $this->getSaveDir().'/*.bakup';
        foreach(glob($glob) as $f){
            $this->info('删除: '.$f);
            $status &= @unlink($f);
        }
        return $status;
    }
}
