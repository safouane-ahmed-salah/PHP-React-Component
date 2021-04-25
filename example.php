<?php

namespace React\Tag;
use React\Component;

include_once 'React.php';

class Header extends Component{
    function render(){
        return new div('This is Header', 
        ['class'=> 'navbar navbar-expand-lg navbar-dark bg-dark p-3 text-white']);
    }
}
class Content extends Component{
    function render(){
        return new div(
                new div(array_map(function(){ 
                return new div(new Card, ['class' => 'col-md-2 my-2']);
            }, range(0, 11)), ['class'=> 'row']), 
        ['class'=> 'container py-2']);
    }
}

class Card extends Component{
    var $state = ['counter' => 1];

    function render(){
        $counter = $this->state->counter;

        return new div([
            new div($counter, ['class'=> 'card-body']),
            new div([
                new button('update state', [
                    'onclick'=> 'phpReact.setState("'.$this->id.'", prevState => ({counter: prevState.counter + 1}))',
                    'class' => 'm-auto btn btn-primary'
                ]),
            ], ['class'=> 'card-footer'])
        ], ['class'=> 'card', 'id'=> $this->id]);
    }
}

class Footer extends Component{
    function render(){
        return new div('This is Footer', 
        ['style'=> 'border-top:1px solid;padding:5px;font-size:18px;position:absolute;bottom:0;width:100%']);
    }
}

class App extends Component{
    function render(){
        return [
            new link(['href'=> 'https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta3/dist/css/bootstrap.min.css', 'rel'=> 'stylesheet']),
            new Header,
            new Content,
            new Footer,
            new script(null,['src'=> 'https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta3/dist/js/bootstrap.bundle.min.js']),
        ];
    }
}

echo new App;
