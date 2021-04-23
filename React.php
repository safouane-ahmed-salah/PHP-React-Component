<?php

namespace React;

abstract class Component{
    private static $isTagsSet = false;
    private static $htmlTags = ['div','p','img','h1','h2','h3','h4','h5','h6','iframe','article', 'form','input','textarea','select','option', 'link', 'script', 'button'];
    private static $hasNoChild = ['img', 'link', 'input'];
    private const tagNameSpace= 'React\Tag';
    private static $counter = 1;
    protected $id = '';
    protected $state = [];

    private function setTags(){
        foreach(self::$htmlTags as $el){
            eval("namespace ". self::tagNameSpace ."; class $el extends \React\Component{}");
        }
        self::$isTagsSet = true;

        //script tag to setup setState function
        echo new \React\Tag\script('const phpReact={setState:function(t,e){var n=document.getElementById(t);if(n){var a=new XMLHttpRequest;a.onreadystatechange=function(){4==this.readyState&&200==this.status&&(n.outerHTML=this.responseText)},a.open("POST",location.href,!0),a.setRequestHeader("Content-type","application/x-www-form-urlencoded"),a.send("phpreact="+JSON.stringify({id:t,state:e}))}}};');
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
        
        $tag = $this->getTagName();
        $attr = []; 
        foreach($this->props as $k=> $v){ $attr[] = $k.'="'.htmlspecialchars($v).'"'; }
        $attributes = implode(' ',$attr);
        $children = implode('', $this->children);

        return "<$tag $attributes>$children</$tag>";
    }

    function __toString(){
        return ''.$this->render();
    }

    function __construct($children = [], $props = []){
        if(!self::$isTagsSet) $this->setTags();
        $hasNoChild = $this->hasNoChild();
        $this->setId();
        $this->setStateListener();

        //set properties
        $this->props = (object)($hasNoChild ? $children : $props);
        $this->children = (array)($hasNoChild ? [] : $children);
    }

    function componentDidUpdate($oldState, $currentState){}

    private function setId(){
        if($this->isHtmlTage()) return;
        $this->id = md5(self::$counter);
        self::$counter++;
    }

    private function setStateListener(){
        if(empty($_POST['phpreact'])) return;
        $post = json_decode($_POST['phpreact']);
        if($post->id != $this->id) return;
        @ob_start();
        @ob_end_clean();
        $oldState = $this->state;
        $this->state = array_merge($oldState, (array)$post->state);
        $this->componentDidUpdate($oldState, $this->state); 
        die($this);
    }
}
