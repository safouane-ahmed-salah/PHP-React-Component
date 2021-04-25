<?php

namespace React;

abstract class Component{
    private static $isTagsSet = false;
    private static $htmlTags = ['div','p','img','h1','h2','h3','h4','h5','h6','iframe','article', 'form','input','textarea','select','option', 'link', 'script', 'button', 'nav', 'title', 'meta'];
    private static $hasNoChild = ['img', 'link', 'input', 'meta'];
    private const tagNameSpace= 'React\Tag';
    private static $counter = 1;
    protected $id = '';
    protected $state = [];
    private static $states = [];

    private function setTags(){
        @ob_start();
        foreach(self::$htmlTags as $el){
            eval("namespace ". self::tagNameSpace ."; class $el extends \React\Component{}");
        }
        self::$isTagsSet = true;

        //script tag to setup setState function
        echo new \React\Tag\script('const phpReact={setState:function(t,e,n){var a=document.getElementById(t);if(a){var r=this.getState(t);"function"==typeof e&&(e=e(r));var o=new XMLHttpRequest;o.onreadystatechange=function(){4==this.readyState&&200==this.status&&(a.outerHTML=this.responseText,"function"==typeof n&&n())},o.open("POST",location.href,!0),o.setRequestHeader("Content-type","application/x-www-form-urlencoded"),o.send("phpreact="+JSON.stringify({id:t,state:e,prevState:r}))}},getState:function(t){try{var e=document.getElementById(t);return JSON.parse(e.getAttribute("prevstate"))}catch(t){return{}}}};');
    }
    
    private function getTagName(){
        return trim(str_replace(self::tagNameSpace, '', get_class($this)), '\\');
    }
    private function isHtmlTage(){
        return in_array($this->getTagName(), self::$htmlTags);
    }
    private function hasNoChild(){
        return in_array($this->getTagName(), self::$hasNoChild);
    }

    static function registerTag($tags, $hasNoChild = false){
        self::$htmlTags= array_unique(array_merge(self::$htmlTags, (array)$tags));
        if($hasNoChild) $this->setHasNoChild($tags); 
    }

    static function setHasNoChild($tags){
        self::$hasNoChild= array_unique(array_merge(self::$hasNoChild, (array)$tags));
    }

    function render(){
        if(!$this->isHtmlTage()) return '';
        
        //save states in dom attribute [prevState]
        if($this->props->id && self::$states[$this->props->id]) 
            $this->props->prevState = json_encode(self::$states[$this->props->id]);

        $tag = $this->getTagName();
        $attr = []; 
        foreach($this->props as $k=> $v){ $attr[] = $k.'="'.htmlspecialchars($v).'"'; }
        $attributes = implode(' ',$attr);
        $children = implode('', $this->children);

        return "<$tag $attributes>$children</$tag>";
    }

    function __toString(){
        $components = $this->render();
        if(!is_array($components)) $components = [$components];
        return implode('', $components);
    }

    function __construct($children = [], $props = []){
        if(!self::$isTagsSet) $this->setTags();
        $hasNoChild = $this->hasNoChild();
        $this->setId();

        if(!is_array($children)) $children = [$children];

        //set properties
        $this->props = (object)($hasNoChild ? $children : $props);
        $this->children = $hasNoChild ? [] : $children;
        $this->state = (object)$this->state;

        //listen to state change
        $this->setStateListener();
    }

    function componentDidUpdate($oldState, $currentState){}

    private function setId(){
        if($this->isHtmlTage()) return;
        $this->id = md5(self::$counter); //generate id
        self::$states[$this->id] = $this->state; //save all states by id
        self::$counter++;
    }

    private function setStateListener(){
        if(empty($_POST['phpreact'])) return;
        $post = json_decode($_POST['phpreact']);
        if(!$post || $post->id != $this->id) return;
        $oldState = $post->prevState;
        $this->state = (object)array_merge((array)$oldState, (array)$post->state);
        self::$states[$this->id] = $this->state;
        $this->componentDidUpdate($oldState, $this->state); 
        @ob_end_clean();
        die($this);
    }
}
