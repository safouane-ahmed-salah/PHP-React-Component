<?php

namespace React;

abstract class Component{
    private static $isTagsSet = false; //flag to track if all html tag classes created

    //all html tags that are allowed
    private static $htmlTags = ['div','p','img','a','ul','li', 'h1','h2','h3','h4','h5','h6','iframe','article', 'form','input','textarea','select','option', 'link', 'script', 'button', 'nav', 'title', 'meta', 'code', 'pre', 'abbr'];
    
    private static $hasNoChild = ['img', 'link', 'input', 'meta']; //tags that have no children 
    private const tagNameSpace= 'React\Tag'; //name space for the tags
    
    private static $counter = 1; // couter for generating sequencial id
    protected $id = ''; //the current id of the component
    protected $state = []; //the current state
    private static $states = []; //used to save all states of every component in the page

    /*
        run the first time when first component called 
        responsible for:
        - setting all tags class component
        - setting the script that controls the state
    */
    private function setTags(){
        @ob_start();
        foreach(self::$htmlTags as $el){
            eval("namespace ". self::tagNameSpace ."; class $el extends \React\Component{}");
        }
        self::$isTagsSet = true;

        //script tag to setup setState function
        echo new \React\Tag\script('const phpReact={setState:function(t,e,n){var a=document.getElementById(t);if(a){var r=this.getState(t);"function"==typeof e&&(e=e(r));var o=new XMLHttpRequest;o.onreadystatechange=function(){4==this.readyState&&200==this.status&&(a.outerHTML=this.responseText,"function"==typeof n&&n())},o.open("POST",location.href,!0),o.setRequestHeader("Content-type","application/x-www-form-urlencoded"),o.send("phpreact="+JSON.stringify({id:t,state:e,prevState:r}))}},getState:function(t){try{var e=document.getElementById(t);return JSON.parse(e.getAttribute("prevstate"))}catch(t){return{}}}};');
    }
    
    /*
        @return the current component tag name
    */
    protected function getTagName(){
        return trim(str_replace(self::tagNameSpace, '', get_class($this)), '\\');
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
        self::$htmlTags= array_unique(array_merge(self::$htmlTags, (array)$tags));
        if($hasNoChild) $this->setHasNoChild($tags); 
    }

    /*
        set the custom tags that has no children
        @param: $tag: string|array[list of string] html tags   
    */
    static function setHasNoChild($tags){
        self::$hasNoChild= array_unique(array_merge(self::$hasNoChild, (array)$tags));
    }

    /*
        render the html tag only
    */
    function render(){
        if(!$this->isHtmlTage()) return '';
        
        //save states in dom attribute [prevstate]
        if($this->props->id && self::$states[$this->props->id]) 
            $this->props->prevstate = json_encode(self::$states[$this->props->id]);

        $tag = $this->getTagName();
        $innerHtml = '';
        $attr = []; 
        foreach($this->props as $k=> $v){ 
            if($k == 'dangerouslyInnerHTML'){ //if has dangerouslyInnerHTML attribute
                $innerHtml = $v; continue;
            } 
            $att = preg_replace('/[^\w-]/','', $k); //allow only [words or dash]
            $val = htmlspecialchars($v); //escape html

            $attr[] = "$att='$v'"; 
        }
        $attributes = implode(' ',$attr);

        //if theres innerHtml then ignore children else escape any string passed as html 
        $children = $innerHtml ? [$innerHtml] : array_map(function($v){ return is_string($v) ? htmlspecialchars($v) : $v; }, $this->children);
        $children = implode('', $children);

        return "<$tag $attributes>$children</$tag>";
    }

    /*
        parse the components to html
        @return: html string 
    */
    function __toString(){
        $components = $this->render();
        if(!is_array($components)) $components = [$components]; //must be list of components

        //if custom component the render should return component or list of components
        if(!$this->isHtmlTage()) $components = array_filter($components, function($v){ return $v instanceof Component; });
        
        return implode('', $components);
    }

    /*
        construct the tag with list of child component and props 
        @param: $children: component|array[of component]  
        @param: $props: associative array of key=> value
        
        @usage: Component($children, $props) or Component($props) if exists in hasNoChild 
    */
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

    /*
        run only when state has been updated
        @param: $oldState: [object] the previous state
        @param: $currentState: [object] the current state
    */
    function componentDidUpdate($oldState, $currentState){}

    /*
        set unique id of each custom component
        it's used to check against when state updated
    */
    private function setId(){
        if($this->isHtmlTage()) return;
        $this->id = md5(self::$counter); //generate id
        self::$states[$this->id] = $this->state; //save all states by id
        self::$counter++;
    }

    /*
        ajax listner of state update
        ajax posts 'phpreact' with json that has attributes [id: component id, state: the new state, prevState: the current state];
    */
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
