<?php
/**
 * Class description
 *
 * @author  Safouane Ahmed Salah
 * @license MIT 
 */

namespace React;


abstract class Component extends React{
    /**
     * Associative array that holds the state of components
     *
     * @var array
    */
    protected $state = []; 

    /**
     * the queue that holds all previous component
     * used for registering the previous states
     * 
     * @var array
    */
    private static $queue = []; //queue of components 
    

    /**
     * the post object when state is changes
     * 
     * @var object
    */
    private static $isSetState = false; 

    /** 
     * Setup the javascript of handling state
     * 
    */
    static function setup(): void {
        //script tag to setup setState function
        self::import('phpreact.js', '1.3');
    }

    /**  
     * Check if the render is for updating a state
     * 
     * @return bool
    */
    static function isSetState(): bool{
        return self::$isSetState;
    }

    static function renderState(){
        $post = json_decode(@$_POST['phpreact']);
        if(!$post) return;
        self::$isSetState = true;
        self::$queue = $post->components;
        $component = self::decode($post->current);
        $oldState = $component->state;
        $component->state = (object)array_merge((array)$oldState, (array)$post->state);
        $component->componentDidUpdate($oldState, $component->state);
        return $component->handleRender();
    }

    private static function decode(string $encode){
        $arr = json_decode(base64_decode($encode));
        include_once $arr->file;
        return unserialize($arr->component);
    }

    private function encode(){
        $ref = $this instanceof Func ? new \ReflectionFunction(get_class($this)) :  new \ReflectionClass($this);
        $file = $ref->getFileName();
        return base64_encode(json_encode(['file'=> $file, 'component'=> serialize($this)]));
    }

    /**  
     * Retrieve the component from the queue
     * 
     * @return Component
    */
    private function getQueueComponent(){
        $encode = array_shift(self::$queue);
        return $encode ? self::decode($encode) : null;
    }

    /**  
     * Render the update state
     * 
     * @return string
    */
    private function stateManager(): string {
        $component = $this->getQueueComponent();
        if(!$component) $component = $this;
        return $component->handleRender();
    }

    /**  
     * Convert a Component to html string 
     * 
     * @return string
    */
    private function handleRender(): string {
        $this->beforeRender();
        $components = $this->render();

        if((array)$this->state && $components instanceof Tag){
            // $componentProps =  ['component'=> base64_encode(serialize($this)), 'component-state'=> $this->state];
            $componentProps =  ['component'=> $this->encode(), 'component-state'=> $this->state];
            $components->props = (object)array_merge((array)$components->props,$componentProps);
        }

        if(!is_array($components)) $components = [$components]; //must be list of components

        $components = array_map(function($v){ return $v instanceof React ? $v : htmlspecialchars((string)$v); }, $components);

        return implode('', $components);
    }

    /**  
     * Convert a component to html string 
     * 
     * @return string
    */
    function __toString(): string {
        return self::isSetState() ? $this->stateManager() : $this->handleRender();
    }


    /**  
     * Construct the tag with list of child component and props 
     * 
     * 2 possible usage: Component($children, $props) or Component($props)  
     * 
     * @param string|array|Component $children 
     *  1- string|[string] allowed only if html tag
     *  2- associative array then it will be considered props
     *  3- Component|[Component] 
     * @param array $props associative array of key=> value
     * 
    */
    function __construct($args = [], $children = []){
        parent::__construct($args, $children);
        $this->state = (object)$this->state;
    }

    /** 
     * Hook when state is changed
     * 
     * @param object $oldState  the previous state 
     * @param object $currentState the current state
     * 
     * @return void 
    */
    function componentDidUpdate($oldState, $currentState){}

    /** 
     * Hook before rendering the component
     * 
     * @return void 
    */
    function beforeRender(){}
}
