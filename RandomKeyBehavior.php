<?php
/**
 * Generate a new random key of a given integer length to be used as a UID.
 *
 * This is a behavior extension for the Yii Framework version 1.1.x
 * It adds a method to generate a random key of a desired number of digits for use as a primary key in your database.
 * You will still need to do unique collision tests on inserts, but this will give you the security obfuscation
 * advantages of a non-sequential primary key when it will be exposed to the user, but still retain the performance
 * of an integer based primary key value.
 *
 * <b>Usage:</b>
 *
 * The behavior can be attached to a specific model, controller, or component. To make it easily accessible
 * application wide, you may wish to attach it to your application's db component.
 *
 * Add the following parameters to the components section of your config
 * <code>
 *      'components' => array(
 *          'db'=>array(
 *              ...
 *
 *              'behaviors'=>array(
 *                  'uid'=>array(
 *                      'class'    => 'vendor.rlmckenney.yii-random-key.RandomKeyBehavior',
 *                      'dataType' => 'MEDIUMINT',  // optionally set your own default property values
 *                      'digits'   => 8,            // optionally set your own default property values
 *                  ),
 *              ),
 *          ),
 *      ),
 * </code>
 *
 * Then in your model when you need to generate a new primary key:
 *
 * <code>
 *      Yii::app()->db->uid->dataType   = 'BIGINT';  // optionally override the default property values
 *      Yii::app()->db->uid->digits     = 15;        // optionally override the default property values
 *      $id = Yii::app()->db->uid->randomKey;
 * </code>
 *
 *
 * @author Robert McKenney <robert@mckenney.ca>
 * @link http://robertmckenney.info/
 * @link http://www.boldbeaver.com/
 * @copyright 2013, Robert L. McKenney
 * @license http://opensource.org/licenses/mit-license.html MIT
 *
 */

/**
 * Class RandomKeyBehavior
 *
 * @property string $dataType The target data type for storing your primary key. Defaults to INT - @see Constants.
 * @property int $digits      Sets the length of the random key to be generated. Defaults to 10. Results will always be
 *                            that number of digits - these are integers not strings, so there will be no zero fills.
 *
 */
class RandomKeyBehavior extends CBehavior
{
    /**
     * Constants
     *
     * Max unsigned value for the storage size of the target primary key.
     */
    const TINY_INT   = 'TINYINT';    //  8 bit - Max  3 digits
    const SMALL_INT  = 'SMALLINT';   // 16 bit - Max  5 digits
    const MEDIUM_INT = 'MEDIUMINT';  // 24 bit - Max  8 digits
    const INT        = 'INT';        // 32 bit - Max 10 digits
    const BIG_INT    = 'BIGINT';     // 64 bit - Max 18 digits (see note)

    /**
     * NOTE: PHP does not support unsigned integers, so the max value here is half of the full range on MySQL.
     *       If you want to use the full range of BIG_INT, I recommend that you use the UUID_SHORT() function in MySQL.
     *       @link http://dev.mysql.com/doc/refman/5.6/en/miscellaneous-functions.html#function_uuid-short
     */

    public      $dataType = 'INT';
    protected   $_digits  = 10;

    
    /* This should be the main method called to use this behaviour with a given class.
     * It will test the randomKey generated against the existing ids in the table for the given class,
     * and only return a value when there is no conflict.
     *
     * Note: You will want to commit the returned value immediately to ensure that no conflict exists.
     *
     * @param string $className     for which to generate the unique id
     *
     * @return int $uid             ten digit number
     */
    public function getNewId($className)
    {
        $table = $className::model()->tableName();
        $sql = <<<SQL
        SELECT COUNT(id)
          FROM {$table}
         WHERE id = :uid;
SQL;
        $cmd = Yii::app()->db->createCommand($sql);
        $isNotUnique = true;
        while ($isNotUnique) {
            $uid = $this->getRandomKey();
            $isNotUnique = $cmd->queryScalar(array(':uid' => $uid));
        }
        $cmd->reset();

        return $uid;
    }


    /**
     * Generate a new random key that can be used as a primary key field for database inserts.
     *
     * @return int          Random number of length $digits up to a maximum value of $maxValue
     * @throws CException   If requested digits is larger than the number of digits for $maxValue
     */
    public function getRandomKey()
    {
        if ($this->digits > ceil(log10($this->maxValue))) {
            throw new CException('RandomKey ERROR: Requested $digits exceeds $maxValue');
        }

        do {
            $min = pow(10, $this->digits - 1);
            $max = pow(10, $this->digits) - 1;
            mt_srand();
            $newKey = mt_rand($min, $max);
        } while ($newKey > $this->maxValue);

        return (int)$newKey;
    }

    /**
     * @return int
     */
    public function getDigits()
    {
        return $this->_digits;
    }

    /**
     * Magic method for setting the digits property.
     *
     * Checks to ensure the requested number of digits is in bounds for the target data type.
     * Also, applies a lower limit if running on a 32 bit OS.
     *
     * @param int $digits
     * @throws CException
     */
    public function setDigits($digits)
    {
        if (PHP_INT_SIZE == 4) {
            if ($digits > 0 && $digits < 10) {
                $this->_digits = $digits;
            } else {
                throw new CException('RandomKey ERROR: The value for $digits must be between 1 and 9');
            }
        } else {
            if ($digits > 0 && $digits < 19) {
                $this->_digits = $digits;
            } else {
                throw new CException('RandomKey ERROR: The value for $digits must be between 1 and 18');
            }
        }

    }

    /**
     * Lookup the maximum allowed integer value for the target data type.
     *
     * @return int
     */
    public function getMaxValue()
    {
        switch (PHP_INT_SIZE) {

            case 4:
                switch ($this->dataType) {
                    case self::MEDIUM_INT:
                        $maxValue = 16777215;
                        break;
                    case self::SMALL_INT:
                        $maxValue = 65535;
                        break;
                    case self::TINY_INT:
                        $maxValue = 255;
                        break;
                    case self::INT:
                    default:
                        $maxValue = 2147483646;
                }
                break;

            case 8:
                switch ($this->dataType) {
                    case self::BIG_INT:
                        $maxValue = 9223372036854775807;  // PHP does not support unsigned integers
                        break;
                    case self::MEDIUM_INT:
                        $maxValue = 16777215;
                        break;
                    case self::SMALL_INT:
                        $maxValue = 65535;
                        break;
                    case self::TINY_INT:
                        $maxValue = 255;
                        break;
                    case self::INT:
                    default:
                        $maxValue = 4294967295;
                }
                break;
        }

        return $maxValue;
    }
}
