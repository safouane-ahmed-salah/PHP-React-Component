<?php

namespace React\Tag;
use React\Component;

include_once 'React.php';

Component::registerTag('safwan');

class CustomComponent extends Component{
    var $state = ['test' => 1];
    
    function componentDidUpdate($prevState, $currState){
    }
    
    function render(){
        $test = $this->state['test'];
        
        return new div([ 
            new p('Hello World', ['style'=> 'color:red;background:blue']), 
            new div('Many div'),
            new button("set my state ($test)", ['onclick'=> "phpReact.setState('$this->id', {test: ".($test+1)."})"]) 
        ], ['style'=> 'border:1px solid #eee;border-radius:4px;max-width:500px;padding:5px;margin:10px', 'id'=> $this->id]);
    }
}

class App extends Component{
    function render(){
        $customs = [];
        for($i=0;$i<3; $i++){
            $customs[] = new CustomComponent;
        }

        return new div([
            new safwan('Hi I am safwan tag', ['onclick'=> 'alert("hello")']), 
            new form([
                new img(['src'=> 'https://image.flaticon.com/icons/png/512/1453/1453608.png']),
                new select([
                    new option('hi'),
                    new option('hello'),
                ], ['name'=> 'select']),
                new input(['type'=>'submit'])
            ])
        ] + $customs );
    }
}

echo new App;
