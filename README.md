Yii RandomKey
==============

A Yii Framework behavior class to generate UIDs of a given integer length and target data type.

This is a behavior extension for the Yii Framework version 1.1.x

It adds a method to generate a random key of a desired number of digits for use as a primary key in your database.
This will give you the security obfuscation advantages of a non-sequential primary key when it will be exposed to the user, but still retain the performance
of an integer based primary key value.

**UPDATED:**
There is a new method <code>getNewId($className)</code>. This should be the main method called to use this behaviour with a given class. It uses the existing getRandomKey() method and will test the randomKey generated against the existing ids in the table for the given class. If there is a conflict, it will generate another key to test and only return a value when there is no conflict.

The following target data types have the associated maximum digits:
<pre>
'TINYINT'   =>  8 bit - Max  3 digits
'SMALLINT'  => 16 bit - Max  5 digits
'MEDIUMINT' => 24 bit - Max  8 digits
'INT'       => 32 bit - Max 10 digits
'BIGINT'    => 64 bit - Max 18 digits (see note)
</pre>


**NOTE:**
PHP does not support unsigned integers, so the max value for BIGINT is half of the full range on MySQL.
If you want to use the full range of BIG_INT, I recommend that you use the UUID_SHORT() function in MySQL.
http://dev.mysql.com/doc/refman/5.6/en/miscellaneous-functions.html#function_uuid-short

It will automatically detect if you are running on a 32 bit OS and impose reduced limits. 
BIGINT will not be available and INT max digits = 9 with max value of 999999999 instead of the full 4294967295.


Usage
------

The behavior can be attached to a specific model, controller, or component. To make it easily accessible
application wide, you may wish to attach it to your application's db component.

Add the following parameters to the components section of your config
```php
'components' => array(
  'db'=>array(
      ...
  
      'behaviors'=>array(
        'uid'=>array(
            'class'    => 'vendor.rlmckenney.yii-random-key.RandomKeyBehavior',
            'dataType' => 'MEDIUMINT',  // optionally set your own default property values
            'digits'   => 8,            // optionally set your own default property values
            ),
        ),
    ),
),
```
If you omit the dataType and digits property values from the config, the behavior will default to
```php
'dataType' => 'INT'
'digits'   => 10
```
Then in your model when you need to generate a new primary key, simply call the getNewID() method:
```php
$id = Yii::app()->db->uid->getNewId('User');  // gets new key value and tests for conflicts with
                                              // existing IDs in the table for the given class name
                                              // in this example 'User'.
```
Or, to override your defaults, call it like this:
```php
Yii::app()->db->uid->dataType   = 'BIGINT';  // optionally override the default property values
Yii::app()->db->uid->digits     = 15;        // optionally override the default property values
$id = Yii::app()->db->uid->getNewId('User');
```
