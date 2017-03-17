<?php

class template
{
    
    static public $fileDir = null;
    static public $cacheDir = null;
    static public $fileExtension = '.html';
    static public $cacheExtension = '.php';
    static public $forceRecache = false;
    protected $file = null;
    protected $cache = null;
    protected $isCached = true;
    protected $vars = array();
    
    public function __construct($template, $recache = false)
    {
        $this->file = rtrim(self::$fileDir, '/').'/'.$template.self::$fileExtension;
        $this->cache = rtrim(self::$cacheDir, '/').'/'.$template.self::$cacheExtension;
        if(!$this->isCached() || $recache || self::$forceRecache)
            $this->isCached = false;
    }
    
    public function assign($key, $value)
    {
        $this->vars[$key] = $value;
    }
    
    public function assignGroup($data)
    {
        foreach($data as $unique)
        {
            $this->vars[$unique['key']] = $unique['value'];
        }
    }
    
    public function show()
    {
        $this->process();
        $_ = $this->vars;
        extract($_);
        include($this->cache);
    }
    
    public function process()
    {
        if(!$this->isCached) $this->parse();
        return $this->cache;
    }
    
    protected function isCached()
    {
        return file_exists($this->cache);
    }
    
    public function parse()
    {
        $tpl = file_get_contents($this->file);
        $tpl = $this->replaceDefaults($tpl);
        $tags = json_parse(file_get_contents(__dir__.'/tags.json'));
        $tpl = $this->replaceTags($tpl, $tags);
        @file_put_contents($this->cache, $tpl);
    }
    
    protected function replaceDefaults($tpl)
    {
        // variables
        $regex = '/\{\$([^\}]*)\}/';
        $regex2 = '<?=\$$1;?>';
        $tpl = preg_replace($regex, $regex2, $tpl);
        // constantes
        $regex = '/\{\#([a-zA-Z0-9_]*)\#\}/';
        $regex2 = '<?=$1;?>';
        $tpl = preg_replace($regex, $regex2, $tpl);
        return $tpl;
    }
    
    protected function replaceTags($tpl, $tags)
    {
        foreach($tags as $tag)
        {
            $append = null;
            if(!empty($tag->regex)) $append = '="'.$tag->regex.'"';
            $realRegex = '/\{'.$tag->tag.$append.'\}/';
            $substitution = '<?php '.$tag->php.' ?>';
            $tpl = preg_replace($realRegex, $substitution, $tpl);
            if($tag->close)
            {
                $closeRegex = '/\{/'.$tag->tag.'\}/';
                $substitution = '<?php '.$tag->close_php.' ?>';
                $tpl = preg_replace($closeRegex, $substitution, $tpl);
            }
        }
        return $tpl;
    }
    
    protected function replaceImports($tpl)
    {
        $matches = array();
        $regex = '/\{import="([^\"]+)"\}/';
        preg_match_all($regex, $tpl, $matches, PREG_OFFSET_CAPTURE);
        foreach($matches as $match)
        {
            $file = $match[1];
            $tplObject = new template($file);
            $repl = $tplObject->process();
            $tpl = str_replace('{import="'.$file.'"}', '<?php require('.$repl.'); ?>', $tpl);
        }
    }
}