# PHP-React-Component

This project aims to mimic react component in php

it's one of the elegant solution to make component based web app via php

# Installation:
`composer require phpreact/component`

# Usage:

To create a component you neet to just extend React\Component class

```php
namespace React\Tag;
use React\Component;

class CustomComponent extends Component{
    function render(){
        return new div(['style'=> 'border:1px solid #eee;border-radius:4px;max-width:500px;padding:5px;margin:10px'],[ 
            new p(['style'=> 'color:red;background:blue'], 'Hello World'), 
            new div('Many div') 
        ]);
    }
}
```

HTML tags are create via the React Component class
all HTML tags are under namespace React\Tag

To register custom html tag
you just call a static function registerTag
```php
React\Tag::register('tag1');
React\Tag::register(['newtag1', 'newtag2']); //multiple html tags
```

To render your app
```php
echo new CustomComponent;
```


we can now have the ability to mimic reactjs state management.

there some slight difference in apply setState as we need to connect js event setState to php component

Note: make sure the component is wrapped by htmltag (ie: p, div, ...)

```php
class CustomComponent extends Component{
    var $state = ['test' => 1];
    
    function componentDidUpdate($prevState, $currState){} //run only when there's state update
    
    function render(){
        $test = $this->state->test;
        
        return new div(['style'=> 'border:1px solid #eee;border-radius:4px;max-width:500px;padding:5px;margin:10px'],[ 
            new p(['style'=> 'color:red;background:blue'], 'Hello World',), 
            new div('Many div'),
            new button(['onclick'=> "this.setState({test: ".($test+1)."})"], "set my state ($test)") 
        ]); 
    }
}
```




# Sample full example

```php
namespace React\Tag;    
use React\Component;


include_once 'vendor/autoload.php';

//functional Component
function Cars($children = [], $text){
    return new div(['style'=>'display:flex'], array_map(function($v) use($text){ 
        return  new Car(['text'=> $v . $text]); 
    }, $children));
}

function Car($text = ''){
    $state = \React\useState(['test'=> 1]);
    return new div(['onclick'=> 'this.setState(function(state){ return {test: state.test + 1}})','style'=> [
        'color'=> '#fff', 
        'border-style'=>'solid', 
        'border-radius'=> 2,
        'padding'=>5, 
        'border-color'=> 'red', 
        'border-width'=> 2,
        'margin' => 5,
        'background'=> 'brown',
        'width'=> '100%',
        'text-align'=> 'center'
        ]], $text . ' '. $state->test); 
}

class App extends Component{
    function render(){
        return [
            new div(['style'=>'color:red'],'test world'),
            new div(['style'=>'color:red'], 'cool'),
            new Cars(['text'=> ' hello world'],['Volsvagen', 'Kia', 'via']),
        ];
    }
}

echo new App;
```
