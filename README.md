# PHP-React-Component

This project aims to mimic react component in php

it's one of the elegant solution to make component based web app via php

# Usage:

To create a component you neet to just extend React\Component class

```php
namespace React\Tag;
use React\Component;

class CustomComponent extends Component{
    function render(){
        return new div([ 
            new p('Hello World', ['style'=> 'color:red;background:blue']), 
            new div('Many div') 
        ], ['style'=> 'border:1px solid #eee;border-radius:4px;max-width:500px;padding:5px;margin:10px']);
    }
}
```

HTML tags are create via the React Component class
all HTML tags are under namespace React\Tag

To register custom html tag
you just call a static function registerTag
```php
Component::registerTag('tag1');
Component::registerTag(['newtag1', 'newtag2']); //multiple html tags
Component::registerTag('newtag3', true); //html tag that does not have child elements
```

To render your app
```php
echo new CustomComponent;
```

# Sample full example

```php
namespace React\Tag;
use React\Component;

include_once 'react.php';

Component::registerTag('safwan');

class CustomComponent extends Component{
    function render(){
        return new div([ 
            new p('Hello World', ['style'=> 'color:red;background:blue']), 
            new div('Many div') 
        ], ['style'=> 'border:1px solid #eee;border-radius:4px;max-width:500px;padding:5px;margin:10px']);
    }
}

class App extends Component{
    function render(){
        $customs = [];
        for($i=0;$i<10; $i++){
            $customs[] = new CustomComponent;
        }

        return new div([
            new safwan('Hi I am safwan tag', ['onclick'=> 'alert("hello")']), //new custom tag
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
```
