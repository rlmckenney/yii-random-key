yii-random-key
==============

A Yii Framework behavior class to generate UIDs of a given integer length and target data type.

This is a behavior extension for the Yii Framework version 1.1.x

It adds a method to generate a random key of a desired number of digits for use as a primary key in your database.
You will still need to do unique collision tests on inserts, but this will give you the security obfuscation
advantages of a non-sequential primary key when it will be exposed to the user, but still retain the performance
of an integer based primary key value.

**Usage:**

The behavior can be attached to a specific model, controller, or component. To make it easily accessible
application wide, you may wish to attach it to your application's db component.

Add the following parameters to the components section of your config
```php
'components' => array(
  'db'=>array(
      ...
  
      'behaviors'=>array(
        'uid'=>array(
            'class'    => 'vendor.rlmckenney.yii-randomkey.RandomKeyBehavior',
            'dataType' => 'MEDIUMINT',  // optionally set your own default property values
            'digits'   => 8,            // optionally set your own default property values
            ),
        ),
    ),
),
```

Then in your model when you need to generate a new primary key:

```
Yii::app()->db->uid->dataType   = 'BIGINT';  // optionally override the default property values
Yii::app()->db->uid->digits     = 15;        // optionally override the default property values
$id = Yii::app()->db->uid->randomKey;
```
