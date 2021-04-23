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
Component::registerTag('newtag3', true); //html tag that does not have children elements
```

to render your app
```php
echo new CustomComponent;
```
