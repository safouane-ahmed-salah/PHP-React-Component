<?php

namespace React;

abstract class Component{
    private static $isTagsSet = false;
    private static $htmlTags = ['div','p','img','h1','h2','h3','h4','h5','h6','iframe','article', 'form','input','textarea','select','option', 'link'];
    private static $hasNoChild = ['img', 'link', 'input'];
    private const tagNameSpace= 'React\Tag';

    private function setTags(){
        foreach(self::$htmlTags as $el){
            eval("namespace ". self::tagNameSpace ."; class $el extends \React\Component{}");
        }
        self::$isTagsSet = true;
    }
    private function getTagName(){
        return trim(str_replace(self::tagNameSpace, '', get_class($this)), '\\');
    }

    static function registerTag($tags, $hasNoChild = false){
        self::$htmlTags= array_unique(array_merge(self::$htmlTags, (array)$tags));
        if($hasNoChild) $this->setHasNoChild($tags); 
    }

    static function setHasNoChild($tags){
        self::$hasNoChild= array_unique(array_merge(self::$hasNoChild, (array)$tags));
    }

    function render(){
        $tag = $this->getTagName();
        if(!in_array($tag, self::$htmlTags)) return '';

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
        $tag = $this->getTagName();
        $hasNoChild = in_array($tag, self::$hasNoChild);
        $this->props = (object)($hasNoChild ? $children : $props);
        $this->children = (array)($hasNoChild ? [] : $children);
    }
}
