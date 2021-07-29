<?php
/**
 * Class description
 *
 * @author  Safouane Ahmed Salah
 * @license MIT 
 */

namespace React;

abstract class Func extends Component{
    /**  
     * Render the class that represent the html tag
     * 
     * @return string
    */
    function render(){
        $fn = get_class($this);
        $ref = new \ReflectionFunction($fn);
        $args = [];
        foreach($ref->getParameters() as $arg){
            $args[] = $this->props->{$arg->getName()} ?? ($arg->isOptional() ? $arg->getDefaultValue() : null);
        }

        return call_user_func_array($fn, $args);
    }

    /**  
     * Get State 
     * 
     * @return object
    */
    function getState(array $default = []){
        $this->state = (object)array_merge($default, (array)$this->state);
        return $this->state;
    } 
}

/**  
 * Get State in functional component
 * 
 * @return object
*/
function useState(array $default = []){
    $trace = debug_backtrace();
    $reactFunc = @$trace[3];
    if(!$reactFunc || !($reactFunc['object'] instanceof Func)) trigger_error("useState must be called inside functional component");
    return $reactFunc['object']->getState($default);
}