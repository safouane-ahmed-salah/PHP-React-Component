<?php

namespace React;

abstract class Component{
    private static $isTagsSet = false; //flag to track if all html tag classes created

    //all html tags that are allowed
    private static $htmlTags = ['div','p','img','small', 'a','ul','li', 'h1','h2','h3','h4','h5','h6','iframe','article', 'form','input','textarea','select','option', 'link', 'script', 'button', 'nav', 'title', 'meta', 'code', 'pre', 'span', 'i', 'svg', 'path', 'circle', 'g'];
    
    private static $hasNoChild = ['img', 'link', 'input', 'meta']; //tags that have no children 
    private const tagNameSpace= 'React\Tag'; //name space for the tags
    
    protected $state = []; //the current state
    protected $props = []; //the props
    protected $children = []; //the children
    private static $queue = []; //queue of components 
    private static $isQueued = false;

    /*
        run the first time when first component called 
        responsible for:
        - setting all tags class component
        - setting the script that controls the state
    */
    static function setTags(){
        @ob_start();
        foreach(self::$htmlTags as $el){
            eval("namespace ". self::tagNameSpace ."; class $el extends \React\Component{}");
        }
        self::$isTagsSet = true;

        //script tag to setup setState function
        echo new \React\Tag\script('!function(t,e){var n=function(){e.querySelectorAll("[component] *").forEach(function(t){t.setState||(t.getState=c,t.setState=o)})},o=function(t,o){var c=this.hasAttribute("component")?this:this.closest("[component]");if(c){var i=[c.getAttribute("component")],r=this.getAttribute("key"),s=(document.activeElement,this.value),a=this.getState();c.querySelectorAll("[component]").forEach(function(t){i.push(t.getAttribute("component"))}),"function"==typeof t&&(t=t(a));var u=new XMLHttpRequest,p={components:i,state:t};u.onreadystatechange=function(){if(4==this.readyState&&200==this.status&&this.responseText){var t,i=e.createElement("div");i.innerHTML=this.responseText,r&&(t=i.querySelector("[key=\'"+r+"\']")),c.replaceWith(i.childNodes[0]),t&&(t.focus(),s&&(t.value="",t.value=s)),"function"==typeof o&&o(),n()}},u.open("POST",location.href,!0),u.setRequestHeader("Content-type","application/x-www-form-urlencoded"),u.send("phpreact="+JSON.stringify(p))}},c=function(){try{var t=this.closest("[component]");return JSON.parse(t.getAttribute("component-state"))}catch(t){return{}}};t.addEventListener("load",n)}(window,document);');
    }
    
    /*
        @return the current component tag name
    */
    protected function getTagName(){
        return strtolower(trim(str_replace(self::tagNameSpace, '', get_class($this)), '\\'));
    }

    /*
        @return check if the current component is html tag
    */
    protected function isHtmlTage(){
        return in_array($this->getTagName(), self::$htmlTags);
    }

    /*
        @return check if the current component is has no children
    */
    protected function hasNoChild(){
        return in_array($this->getTagName(), self::$hasNoChild);
    }

    /*
        register custom html tag
        @param: $tag: string|array[list of string] html tags
        @param: $hasNoChild: bool if the tags accept no children    
    */
    static function registerTag($tags, $hasNoChild = false){
        self::$htmlTags= array_unique(array_merge(self::$htmlTags, self::parseTags($tags)));
        if($hasNoChild) $this->setHasNoChild($tags); 
    }

    /*
        set the custom tags that has no children
        @param: $tag: string|array[list of string] html tags   
    */
    static function setHasNoChild($tags){
        self::$hasNoChild= array_unique(array_merge(self::$hasNoChild, self::parseTags($tags)));
    }

    /* 
        @param: $tags: string|array -- array string to be parse
        @return: parsed array string
    */
    private static function parseTags($tags){
        return array_map(function($tag){ return strtolower(self::parseAttribute($tag)); }, (array)$tags);
    }

    /* 
        allow only [words or dash] for attribute or tag
        @param: $attr: string -- the string to be parse
        @return: parsed string
    */
    private static function parseAttribute($attr){
        return preg_replace('/[^\w-]/','', $attr); //allow only [words or dash]
    }

    /*
        render the html tag only
    */
    function render(){
        if(!$this->isHtmlTage()) return '';

        $tag = $this->getTagName();
        $innerHtml = '';
        $attr = []; 
        foreach($this->props as $k=> $v){ 
            if($k == 'dangerouslyInnerHTML'){ //if has dangerouslyInnerHTML attribute
                $innerHtml = $v; continue;
            } 
            $att = self::parseAttribute($k); //allow only [words or dash]
            $val = htmlspecialchars( is_object($v) || is_array($v) ? json_encode($v) : $v); //escape html

            $attr[] = "$att='$val'"; 
        }

        $attributes = implode(' ',$attr);

        //if theres innerHtml then ignore children else escape any string passed as html 
        $children = $innerHtml ? [$innerHtml] : 
            array_map(function($v)use($tag){ return is_string($v) && $tag!='script' ? htmlspecialchars($v) : $v; }, $this->children);
        $children = implode('', $children);

        return "<$tag $attributes>$children</$tag>";
    }

    private function getQueueComponent(){
        $encode = array_shift(self::$queue);
        return $encode ? unserialize(base64_decode($encode)) : null;
    }

    private function stateManager(){
        $component = null;

        if(!self::$queue && !self::$isQueued){
            if($this->isHtmlTage()) return '';
            $post = json_decode($_POST['phpreact']);
            self::$queue = $post->components;
            self::$isQueued = true;
            $component = $this->getQueueComponent();
            $oldState = $component->state;
            $component->state = (object)array_merge((array)$oldState, (array)$post->state);
            $component->componentDidUpdate($oldState, $component->state);
        }elseif(!$this->isHtmlTage()){
            $component = $this->getQueueComponent();
        }

        if(!$component) $component = $this;

        return $component->handleRender();
    }

    /*
        parse the components to html
        @return: html string 
    */
    private function handleRender(){
        $components = $this->render();

        //save state of custom component in top html wrapper
        if(!$this->isHtmlTage() && $components instanceof Component && $components->isHtmlTage()){
            $components->props = (object)array_merge((array)$components->props, ['component'=> base64_encode(serialize($this)), 'component-state'=> $this->state]);
        }

        if(!is_array($components)) $components = [$components]; //must be list of components

        //if custom component the render should return component or list of components
        if(!$this->isHtmlTage()) $components = array_filter($components, function($v){ return $v instanceof Component; });
        
        return implode('', $components);
    }

    /*
        parse the components to html
        @return: html string 
    */
    function __toString(){
        return empty($_POST['phpreact']) ? $this->handleRender() : $this->stateManager();
    }

    /*
        construct the tag with list of child component and props 
        @param: $children: component|array[of component]  
        @param: $props: associative array of key=> value
        
        @usage: Component($children, $props) or Component($props) if exists in hasNoChild 
    */
    function __construct($children = [], $props = []){
        if(!self::$isTagsSet) self::setTags();
        $hasNoChild = $this->hasNoChild();

        if(!is_array($children)) $children = [$children];

        //set properties
        $this->props = (object)array_merge((array)$this->props, $hasNoChild ? $children : $props);
        $this->children = $hasNoChild ? [] : $children;
        $this->state = (object)$this->state;
    }

    /*
        run only when state has been updated
        @param: $oldState: [object] the previous state
        @param: $currentState: [object] the current state
    */
    function componentDidUpdate($oldState, $currentState){}
}
