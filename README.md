# A custom user-defined syntax expression parsing library

## Getting Started

Install the package to your project using composer:

```bash
composer require sterzik/expression
```

or you may use any PSR-4 compliant autoloader searching classes of the namespace `Sterzik\Expression` in the `src/` subdirectory of the project.

In the examples below we will assume, all classes are imported by the ```use``` statement. Therefore, we will not refer to fully qualified class names, but just to the "terminating" class name expecting there is a ```use``` statement in the begining of the file. For example, if we refer to class ```Parser``` we in fact assume, that there is a statement of:

```php
use Sterzik\Expression\Parser;
```

The hello world application:

```php
require_once(PATH_TO_EXPRESSION_LIBRARY."/autoload.php");
use Sterzik\Expression\Parser;

$parser = new Parser();

#quotes are part of the string, backslash must be double encoded
#because of the php string syntax
$expr = $parser->parse('"Hello world!\\n"'); 
echo $expr->evaluate();
```


## The evaluation process

The process of evaluating an expression is splited in two parts: parsing and evaluating the parsed expression. While the parsing process is responsible for correctly interpret the expression (calculate operator priorities, resolve parenthesis, etc.), the evaluation process is responsible for substituting values to that expression and calculating all operations in the order already given by the parser.

Once an expression is parsed, it may be evaluated multiple times.

### Simple Parsing

```php
$parser = new Parser();
$expression = $parser->parse("1+2*3");
```

The resulting ```$expression``` is an object of class ```Expression``` representing the parsed expression. 


### Simple Evaluation

```php
$value = $expression->evaluate();
```

The evaluate method just substitutes constants to that expression and calculates the result, which should be ```7``` for the expression parsed above.


### Evaluation with variables

```php
$expr = $parser->parse("a+b*(c+d)");
$vars = ["a" => 1, "b" => 2, "c" => 3, "d" => 4];
$result = $expr->evaluate($vars);
```


## Building a custom parser

The greatest power of this library is in the possibility of building custom parsers and custom evaluators. Lets look on custom parsers first.

### The ParserSettings class

The class ```ParserSettings``` acts as a tool for creating custom parsers. To create a custom parser, you need an instance of the ```ParserSettings``` class. The parser settings instance contains information about:

 * defined operators
 * constants
 * variable aliases

#### Obtaining a predefined ParserSettings instance

As of now, there are two predefined instances of the class ```ParserSettings```. 

```php
$defaultParserSettings = ParserSettings::get("default");
$emptyParserSettings = ParserSettings::get("empty");
```

While the default parser settings contain a predefined _default_ list of operators, the empty parser settings is completely clear to define custom operators.


### Defining custom operators

```php
$ps = ParserSettings::get("empty");
$ps->opPriority(1);
$ps->opArity("L");
$ps->addOp("+");
$ps->addOp("*");
```

This piece of code will define two operators: "+" and "*" with the same priority. Using such a parser (how to use custom parsers see below) would cause to evaluate the expression ```1+2*3``` as ```9``` because now both operators have the priority of 1 and left arity (evaluation from left to right).

It is also possible to define operators as identifiers:

```php
$ps->addOp("plus");
```

In such a case, it would be possible to evaluate the expression ```1 plus 2```.


### Defining operator aliases

By default, the name of the operator will be passed even to the evaluator. So if you define an operator '+', the string "+" will be passed to the evaluator. If you define an operator "plus", the string "plus" will be passed to the evaluator. Sometimes, it may be useful to pass a different string to the evaluator than the operator itself. Mostly in cases when you want to define an alias to some another operator. For example you may do this:

```php
$ps->addOp("+");
$ps->addOp("plus", "+");
```

Now both operators "+" and "plus" passes the same operator "+" to the evaluator. In the evaluator it is sufficient to define one function for "+" and you don't need to define the same code for "plus".


### Using custom parsers
First at all you need to create the custom parser from the parser settings:

```php
$parser = new Parser($parserSettings);
```

Now you may use the parser as usual. For example:

```php
$expr = $parser->parse("1 plus 2");
```

You may even define any parser settings as default. In such a case, creating a new parser without arguments will create the parser according to these default parser settings:

```php
$oldDefault = $parserSettings->setDefault();
$parser = new Parser(); #this parser uses $parserSettings as parser settings
$oldDefault->setDefault();
$parser2 = new Parser(); #this parser uses the original parser settings 
```

### Defining custom constants
If you want to define custom constants, you may use this method of ```ParserSettings```:

```php
$parserSettings->addConstant("true", true);
```

Such a parser will evaluate the identifier ```true``` as the php value of true.


### Defining unary operators

```php
$parserSettings->addPrefixOp("++");
$parserSettings->addPostfixOp("--");
```

If you want to define the same operator both, as prefix and even as a postfix operator, you need to distinguish them by the name passed to the evaluator:

```php
$parserSettings->addPrefixOp("++x", "++");
$parserSettings->addPostfixOp("x++", "++");
```

The string passed to the evaluator may be any string with no other restrictions. Therefore, while the string "++x" **may not** be used as an operator, it **may** be used as the alias passed to the evaluator. 


### Defining multinary operators
Multinary operators are operators which take more than two arguments. The classical c operator ```?:``` is a perfect example of such. 

```php
$parserSettings->addMultinaryOp("?:", "?", ":");
```

When calling a multinary operator, the first argument is the alias passed to the evaluator and is mandatory here.

### Defining variadic operators
Variadic operators may take variable number of operands, while all are passed to one function. The arity is not important for variadic operators, because they are evaluated as whole. For example taking a parser with:

```php
$parserSettings->addVariadicOp(",");
```

would evaluate the expression ```a,b,c``` as an operator ```,``` taking three arguments: ```a```, ```b``` and ```c``` (```","(a,b,c)``` in the prefix notation). While if ```,``` would be defined as a standard binary left arity operator, the expression would be evaluated as ```","(","(a,b),c)``` in the prefix notation.


### Defining parenthesis

```php
$parserSettings->addParenthesis("()", "(", ")", false);
```

Arguments of ```addParenthesis```:

 1. the alias passed to the evaluator
 2. the operator causing the parenthesis to open
 3. the operator causing the parenthesis to close
 4. true if 0-argument parenthesis are allowed.


### Defining prefix/postfix parenthesis/indices

You may even define parenthesis as an prefix/postfix unary operator. For example a standard function call in many languages may be understood as a postfix parenthesis/index. For example, the following will define a standard function call operator:

```php
$parserSettings->addPostfixIndex("fn()", "(", ")", true);
```

To define a prefix index, you may for example run:

```php
$parserSettings->addPrefixIndex("<<>>", "<<", ">>", false);
```

which would define a special casting-like operator. An expression using that operator may look like: ```<<A>>B```

**NOTE:** In both prefix and postfix indices the first argument is the expression to which the index is applied and the second argument is the expression in the parenthesis/brackets.

### Handling parsing errors

The parser may be run in two modes depending how it will act on an parsing error:

 1. (default) return ```null``` and the error message is available through calling the method ```getLastError()```.
 2. Throw an exception of class ```ParserException``` if an error occurs.
  
The parsing error mode may be set dynamically for each request or passed directly by the constructor.

#### Handling errors by returning null

```php
$parser = new Parser($parserSettings, false); #creates a parser NOT throwing exceptions
$expr = $parser->parse($expressionString);
if ($expr === null) {
    echo "parser error: " . $parser->getLastError() . "\n";
    return;
} 
```
 
#### Handling error by throwing an exception

```php
$parser = new Parser($parserSettings, true); #creates a parser in exception throwing mode
try {
    $expr = $parser->parse($expressionString);
} catch(ParserException $e) {
    echo "parser error: " . $e->getMessage() . "\n";
    return;
}
```

#### Dynamicaly changing the error handling mode

```php
$parser = new Parser($parserSettings);
$parser->throwExceptions(true); #parser in exception throwing mode
$parser->throwExceptions(false); #parser in "return null" mode
```
 
## Manipulating parsed expressions

Once the expression is successfully parsed, an object of a class ```Expression``` is returned. This object represents the abstract evaluation tree of the expression. There are three ```Expression``` subclasses. Each represents a different type of expressions:

 1. constants
 2. variables
 3. operations
 
 There is one important method of each expression: ```type()``` which will return the string representing the type of that expression:
 
  * **constants** return the type ```const```
  * **variables** return the type ```var```
  * **operations** return the type ```op``` 
 
### Constant expressions

Each constant expression represent one constant. For example, if you parse the expression ```12```, it will result in a constant expression having the value of 12. Accessing attributes of that type:

```php
if ($expr->type() == "const") {
    $value = $expr->value();
    echo "The expression represents a constant with a value of: " . $value . "\n";
}
```

For built-in constants (i.e. strings and integers, maybe floats in a future release of that library), the value of that expression is exactly the scalar by which it is represented in PHP. Meaning, that integers are represented by integers, strings by strings and flotats will be represented by floats if they will be implemented.

User-defined constants are passed exactly as they were defined by the method ```ParserSettings::addConstant()```.

The important point of constants is, that each constant value will be evaluated as well. So you may process the constants in **any** way when evaluated. 


### Variable expressions

Variable expressions represent a variable. Each variable has a name. The name may be in general any string, but the current implementation of the parser will produce only variable expressions whith variable names being c-like identifiers.

Variable expressions may be accessed this way:

```php
if ($expr->type() == "var") {
    $name = $expr->name();
    echo "The expression represents a variable with a name: ".$name."\n";
}
```


### Operation expressions

Operation expressions represent any kind of operations. Each operation has:

 1. an operation identifier (any string)
 2. operation arguments (array of expressions)
 
Operation expressions may be accessed in this way:

```php
if ($expr->type() == "op") {
    $identifier = $expr->op();
    $arguments = $expr->arguments();
    #easy accessing arguments:
    foreach ($expr as $argument) {
        #do something with each argument
    }

}
```


### Creating own expressions

Creating own expressions is mostly useful when doing expression postprocessing (see later)

```php
$constant = Expression::create("const", 15);
$variable = Expression::create("var", "abc");
$operation = Expression::create("op", "+", $constant, $variable); #alternative #1
$operation = Expression::create("op", "+", [$constant, $variable]); #alternative #2
```


### Dumping and restoring expressions

Once an expression is parsed, it may be useful to store it and later reconstruct the expression.

#### Dumping an expression

There are several methods how to dump the expression:

```php
$dataStruct = $expr->dump();       #dumps as a php structure without objects (serializable to json) 
$dataJson   = $expr->dumpJson();   #dumps into a json encoded  string
$dataB64    = $expr->dumpBase64(); #dumps into a base64 encoded string
```

Note: If you want to use the json and base 64 dump methods, your expression **must not** contain any constant not representable in json. For example, if you would create a constant having a value of an php object reference, it will not be representable in json.  But even if you use object references as constants, you may still use the ```dump()``` method.


#### Restoring an expression

From the data dumped as before, the expression may be reconstructed using the following methods:

```php
$expr = Expression::restore($dataStruct);
$expr = Expression::restoreJson($dataJson);
$expr = Expression::restoreBase64($dataB64);
```

The restoring functions are checking the correctness of the data given to restore. If they will be malformed, a ```null``` will be returned instead of an expression.

## Evaluating expressions detailed

If you want to evaluate any expression, you need an evaluator. An evaluator is an objeect responsible for calculating values for each expressions and its subexpressions. The evaluation process is all the time proceeding from leaves to the root of the abstract tree representing the expression.

To build a custom evaluator, you need to define how all three types of expressions (variables, constants and operations) should be evaluated. To evaluate variables, there is a "variable object" available, which may be passed to the evaluator each time an expression is evaluated. It is completely the responsibility of the evaluator, how this "variable object" will be interpreted. While it would be a good practice to understand variable objects as associative arrays, it is not necessary.

It is necessary to understand, that any evaluator depends on the parser used. It is necessary, that an evaluator used to gether with a particular parser needs to implement **all** operations, which the parser is able to generate.

### Building custom evaluators

```php
 # obtain the default evaluator
 # (able to evaluate all operations from the default parser)
 $evaluator = Evaluator::get("default"); 
 
 # obtain the empty evaluator
 # (no operations are defined there)
 $evaluator = Evaluator::get("empty");
```

If you will build a custom evaluator, it is a good idea to begin with the empty evaluator.


### Using a custom evaluator

To use the evaluator in one single place:

```php
$result = $expr->evaluate($varObject, $evaluator);
```

You may also make the evaluator default:

```php
$oldEvaluator = $evaluator->setDefault();
$expr->evaluate($varObject); # $evaluator need not be passed, because it is default now
```

###Defining evaluation operations

#### Defining constant evaluation

```php
$evaluator->defConstant(function ($const) {
    return $const;
});
```

This is the typical example of evaluating constants. In fact, you don't need to define this type of constant evaluation even in the _empty_ evaluator. It is evaluated by default this way.


#### Defining variable evaluation

```php
$evaluator->defVar(function ($var, $varObject) {
    return $varObject[$var];
});
```

Even in this case, the function above is the default how to evaluate variable. You may not define it if you are happy with that one.


#### Defining operations

```php
$evaluator->defOp("+", function ($a, $b) {
    return $a + $b;
});
```

It is even possible to use multiple functions for the same operation. If this is the case, for a particular operation the function, which fits the number of operation arguments best will be choosen. So for example, you may define:

```php
$evaluator->defOp("-", function ($a, $b) {
    return $a-$b;
});
$evaluator->defOp("-", function ($a) {
    return - $a;
});
```

This will cause to use the first function for binary minus, but the second function for unary minus. This feature allows to distinguish operations not only by its name (which is "-" in both cases above) but even by the number of its arguments. While this feature should work correctly in simple examples (like this), it is not a good idea to rely on it in more complicated examples. You may still distinguish operations directly in the parser, which is definitely safer for more complicated situations.

Sometimes, you may want to define a default operation handler:

```php
$evaluator->defOpDefault(function ($operation, ...$arguments) {
    #do something with the operation and theirs arguments
    return $result
});
```

The default handler is invoked if no particular handler for the given operation was found.

In some cases, it would be useful to pass the operation not only in the default case, but even for particular operations. This could be done by passing `true` as the third argument of `defOp`:

```php
$evaluator->defOp("-", function ($op, $a, $b) {
    #do something depending on $op
    return $result;
}, true);
```

Some operations may be more tricky. For example the standard `?:` operator. This operator guarantees, that the second argument is evaluated **only** if the first argument is evaluated as `true` and the third argument is evaluated **only** if the first argument is evaluated as `false`. This is not possible to deal with if method `defOp()` is used, because it all the time evaluates all arguments. For this reason, there is a method `defOpEx`:

```php
$evaluator->defOpEx("?:", function ($cond, $ifTrue, $ifFalse) {
    return $cond->value() ? $ifTrue->value() : $ifFalse->value();
});
``` 

In this case, the correct evaluation is caused by the correct php evaluation of the `?:` operator.


### L-values

The evaluation process is able even to handle assignments and even other types of operations. For this purpose, we will need L-values.

From the point of the system, a L-value is an object of class `LValue`. Such object supports at least two methods:

 1. ```function lvalue()``` - returns self (`$this`) - this is particualry not necessary for lvalues itself, but it becomes more important for L-value wrappers (see later)
 2. ```function value()``` - returns the value, which the L-value should evaluate to 
 
 But in general an L-value may support even other methods. For example:
 
  1. ```function assign($value)``` - assign a value to the variable or memory space represented by the L-value
  2. ```function fncall($arguments)``` - call a function represented by the L-value and pass to that function arguments `$arguments`
  
  But in general, you may define any methods you want. The only method, which really **must** be implemented is the method `value()`. If you want to use assignments, it is a good practice to name the proper method as `assign($value)`, but its completely up to you what meaning do you give to each method.

#### Creating L-values

There are two methods, how to build a custom L-value:

 1. Subclassing the abstract class `LValue` and implement the abstract `value()` method and any other method you want.
 2. Use the L-value builder.
 
 While the first method is quite clear, we will show how to use the second method:
 
```php
 function createVariableLValue($varName, $varObject)
 {
     $builder = new LValueBuilder();
     $builder->value(function () use ($varName, $varObject) {
         return $varObject[$varName];
     });
     $builder->assign(function ($value) use ($varName, $varObject) {
         $varObject[$varName] = $value;
     });
     
     return $builder->getLValue();
     
 }
``` 

#### Using L-Values

You may use L-values as result of any operation in the evaluator. So using the function `createVariableLValue()` defined above, you may assign L-values for example for variables:

```php
$evaluator->defVar(function ($var, $varObject) {
    return createVariableLValue($varName, $varObject);
});
```

**Note:** The example above will work only if `$varObject` will be an object reference. Because to be able to handle assignments, you need to pass the variable object by reference. For this reason, there is a class `Variables`, which act almost like an array, but it is an object, so it is passed as an reference. An example how to use the class `Variables` follows:

```php
$varObject = new Variables(["a" => 1, "b" => 2, "c" => 3]);
$varObject["a"] = 2;
unset($varObject["c"]);
foreach($varObject as $variable => $value) {
    #do something for all variables and its values
}
$varArray = $varObject->asArray(); #convert back to an ordinary array
```

If you want to define an operator, which will assign a value, you may proceed as follows:

```php
$evaluator->defOpEx("=", function ($a, $b) {
    $a->assign($b->value());
});
```

**Note:** The variables `$a` and `$b` does not contain L-values, but L-value wrappers. In fact, before you may access the L-value, you need to evaluate the appropriate subexpression. And this is the difference between l-values and l-value wrappers: l-value wrappers have the same methods like l-values, but calling of them will have the side effect of evaluating the subexpression. It means, that these two pieces of code do different things:

```php
#piece #1
$evaluator->defOpEx("some_unary_operator", function ($arg) {
    return $arg->value() + $arg->value();
});
#piece #2
$evaluator->defOpEx("someoperator", function ($arg) {
    $arg = $arg->lvalue();
    return $arg->value() + $arg->value();
});

```

In the first piece, the subexpression of the unary operator will be evaluated twice, while in the second piece, the subexpression will be evaluated only once.

It is important to mention, that calling `$argument->lvalue()` will produce an L-value even in case the evaluation of a subexpression will produce an ordinary value. It will be simply converted to an L-value with only the `value()` method defined. But it is also possible to control what happens if some unsupported method is called on this lvalue:

```php
$evaluator->defNotLvalue(function () {
    throw new Exception("LValue required for assignments");
});
```

It is also possible to build a default method called everytime if somebody tries to invoke a method of an L-value not defined. For this, the `setDefaultCallback()` method of the `LValueBuilder` may be used:

```php
function createVariableLValue($varName, $varObject)
{
    $builder = new LValueBuilder();
    $builder->value(function () use ($varName, $varObject) {
        return $varObject[$varName];
    });
    $builder->assign(function ($value) use ($varName, $varObject) {
        $varObject[$varName] = $value;
    });

    $builder->setDefaultCallback(function ($method) {
        throw new Exception("Method ".$method." is not supported for that L-value");
    });

    return $builder->getLValue();
}

```
## Expression postprocessing

Sometimes, it is necessary to do some postprocessing of the parsed expression. Otherwise, it may be overcomplicated to evaluate the expression. While you may do any expression postprocessing after your expression gets parsed in any way, there is even a simple mechanism built into the parser to instantly postprocess any expressions. This mechanism is not intended to do complicated postprocessing, but is rather intedned as a postprocessor for simple tasks.

Here are some examples of expression postprocessing:

 * Removing parenthesis from the expression - the parser itself will create an operation even for parenthesis. But in fact, parenthesis are used only to change the order of evaluation of the operators and act just as an identity operator. Therefore it is definitely not necessary to maintain parenthesis in the parsed expression.
 * combine two operations together. For example: if you want to implement a function call operator (called like `function(a,b,c)`) you need effectively combine the postfix index operation `()` with the variadic operation `,` into just a single operation. Because the `,` operator should have the lowest priority, you may expect, that that variadic operation `,` is a direct ancestor of the postfix index operation `()` in the abstract tree representing the expression.
 
 The postprocessor may be set in the instance of `ParserSettings` itself. To setup a postprocessing, just use the method `setPostprocessOp()`:
 
```php
$parserSettings->addParenthesis('()', '(', ')');
$parserSettings->setPostprocessOp('()', function ($expr, $op) {
    return $expr[0];
});
```
 
The function passed to `setPostprocessOp()` method is responsible for the postprocessing. It takes two arguments: the expression to be postprocessed and the operation to be postprocessed. It should return the expression which should be substituted instead the original operation. The whole substitution process goes from leaves of the abstract tree to the root.

A more complicated example:

```php
$parserSettings->addPostfixIndex('fn()', '(', ')', true);
#ensure the op ',' will have the lowest priority
$parserSettings->opPriority('0');
$parserSettings->addVariadicOp(',');
$parserSettings->setPostprocessOp('fn()', function ($expr, $op) {
    # count($expr) is the number of arguments of the 'fn()' operation
    # possibilities for the number of arguments:
    # 0 - impossible
    # 1 - fncall without any argument
    # 2 - fncall with one or more arguments
    # 3 or more - impossible
    if (count($expr) != 2) {
        return $expr;
    }
    $arg = $expr[1];
    #if the second argument is not the ',' operator, we don't need to postprocess anything
    if ($arg->type() != "op" || $arg->op() != ",") {
        return $expr;
    }
    $newArguments = array_merge([$expr[0]], $arg->arguments());
    return Expression::create("op", "fn()", $newArguments);
});
```

The postprocessing function may also return two special values:

 * `null` - same as if the first argument would be returned. `null` means: "_don't change the expression_".
 * `false` - the postprocessing function failed. The parsing process will end up with an error.

It is also possible to postprocess variables and constants:

```php
#convert all variables "parent" to some variable depending on the current context
$parserSettings->setPostprocessVar(function ($expr) {
    if ($expr->name() == "parent") {
        $parentVar = getCurrentParentName(); #obtain somehow the current parent name
        return Expression::create("var", $parentVar);
    }
    return null;
});

#convert all non-string constants to strings
$parserSettings->setPostprocessConst(function ($expr) {
    if (!is_string($expr->value())) {
        return Expression::create("const", (string)$expr->value());
    }
    return null;
});
```

And it is also possible to make an universal postprocessing handler for all operations:

```php
$parserSettings->setDefaultPostprocessOp(function ($expr, $op) {
    return Expression::create("op", "postprocessed:$op", $expr->arguments());
});
```
