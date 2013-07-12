DOMQuery
========

DOMQuery is a PHP library that allows to easily traverse and modify the DOM

Installation
------------

Add in your composer.json:

```js
{
 "require": {
     "artack/dom-query": "*"
 }
}
```

Running the command:

``` bash
$ php composer.phar update artack/dom-query
```

Usage
-------
### Tree Traversal
``` php
$q = DomQuery:create('
    <div>
        <h1>Title</h1>
        <ul>
            <li class="first">first</li>
            <li class="second">second</li>
            <li class="last">last</li>
        </ul>
    </div>'
);

//output: 2
$q->getChildren()->count()

//output: 3
$q->find('li')->count() 

//output: <li class="first">first</li>
$q->find('li')->getFirst()->getHtml()

//output: <li class="last">last</li>
$q->find('li')->getLast()->getHtml()

//output: <li class="second">second</li>
$q->find('li')->filter('.second')->getHtml()

//output: <li class="second">second</li>
$q->find('li')->get(1)->getHtml()

//output: ul
$q->find('li')->getParent()->getName()
```

### DOM Manipulation
``` php

//output: <div><h1>Title</h1><span>Text</span></div>
DomQuery:create('<div><h1>Title</h1></div>')
    ->append('<span>Text</span>')
->getHtml()

//output: <div><span>Text</span><h1>Title</h1></div>
DomQuery:create('<div><h1>Title</h1></div>')
    ->prepend('<span>Text</span>')
->getHtml()

//output: <div><h2>Title H2</h2></div>
DomQuery:create('<div><h1>Title</h1></div>')
    ->find('h1')
        ->replace('<h2>Title H2</h2>')
        ->getParent()
->getHtml()

//output: <div><h2>New Title</h2></div>
DomQuery:create('<div><h1>Title</h1></div>')
    ->find('h1')
        ->replaceInner('New Title')
        ->getParent()
->getHtml()

```
### Attribute Manipulation
``` php

//output: <img src="image.jpg" style="width:12px;" class="image">
DomQuery:create('<img>')
    ->setAttribute('src', 'image.jpg')
    ->setStyle('width', '12px')
    ->addClass('image')
->getHtml()
```

### HTML Output
``` php
//output: <h1>Title</h1>
DomQuery:create('<h1>Title</h1>')->getHtml()

//output: Title
DomQuery:create('<h1>Title</h1>')->getInnerHtml()
```