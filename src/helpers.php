<?php

if(!function_exists('prepend_view_path')){
    function prepend_view_path($path){
        /** @var \Illuminate\View\FileViewFinder $finder */
        $finder = app('view')->getFinder();
        // if ($finder instanceof \Illuminate\View\FileViewFinder){}
        if(method_exists($finder, 'prependLocation')){
            $arr = is_array($path) ? $path : func_get_args();
            foreach($arr as $v){
                $finder->prependLocation($v);
            }
        }
    }
}