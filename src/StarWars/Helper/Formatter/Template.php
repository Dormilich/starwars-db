<?php

namespace StarWars\Helper\Formatter;

use RuntimeException;

/**
 * Assigning placeholder values:
 * 
 * $obj->assign('key', 'value');
 *    - chainable
 *    - catches errors
 * 
 * $obj->render(['key' => 'value']);
 *    - catches errors
 */
class Template implements FormatterInterface
{
    /**
     * @var string $defaultValue A string that any found and unassigned 
     *          placeholder should use when rendering the template.
     */
    public $defaultValue = false;

    /**
     * @var string $tpl Template to process.
     */
    protected $tpl;

    /**
     * @var array $keys The placeholder identifiers.
     */
    protected $keys = [];

    /**
     * @var array $data The values for each placeholder.
     */
    protected $data = [];

    /**
     * @var string $open Opening placeholder delimiter.
     */
    protected $open;

    /**
     * @var string $close Closing placeholder delimiter.
     */
    protected $close;

    /**
     * @var array $errors List of error messages.
     */
    protected $errors = [];

    /**
     * Create instance.
     * 
     * If no closing delimiter is passed, the same string as for the opening 
     * delimiter is used.
     * 
     * @param string $template The template to populate.
     * @param string $open Placeholder opening delimiter.
     * @param string $close Placeholder closing delimiter.
     * @return self
     * @throws RuntimeException Ambiguous placeholders found.
     */
    public function __construct( $template, $open, $close = null )
    {
        $this->setDelimiters( $open, $close );
        $this->setTemplate( $template );
    }

    /**
     * Get the template.
     * 
     * @return string The template.
     */
    public function getTemplate()
    {
        return $this->tpl;
    }

    /**
     * Set the template.
     * 
     * @param string $template The template to populate.
     * @return void
     */
    public function setTemplate( $template )
    {
        $this->tpl = (string) $template;
        $this->setPlaceholderKeys();
    }

    /**
     * Set the opening and closing delimiters of the placeholders.
     * 
     * @param string $open Opening placeholder delimiter.
     * @param string $close Closing placeholder delimiter.
     * @return void
     */
    protected function setDelimiters( $open, $close )
    {
        $this->open = (string) $open;

        if ( null === $close ) {
            $this->close = $this->open;
        } 
        else {
            $this->close = (string) $close;
        }
    }

    /**
     * Find the case-insensitively matching key in the placeholder key list.
     * If a key does not exist in the key list, it is returned unchanged.
     * 
     * This method allows the placeholder names to be entered case-insensitively, 
     * e.g. even if the placeholder name is 'FOO', using $obj['foo'] = 'value' 
     * correctly assigns the placeholder value.
     * 
     * @param string $offset Key candidate.
     * @return string Key.
     * @throws RuntimeException Unknown placeholder key.
     */
    protected function findKey( $offset )
    {
        if ( in_array( $offset, $this->keys, true ) ) {
            return $offset;
        }
        // UTF-8
        foreach ( $this->keys as $key ) {
            if ( mb_strtolower( $key, 'UTF-8' ) === mb_strtolower( $offset, 'UTF-8' ) ) {
                return $key;
            }
        }

        $msg = sprintf( 'Placeholder name "%s" does not exist in the template.', $offset );
        throw new RuntimeException( $msg );
    }

    /**
     * Set a value for a placeholder.
     * 
     * When using this method any exceptions caused by unknown keys are put 
     * into the error array and can be retrieved from there.
     * 
     * @param string $key Placeholder name.
     * @param string $value Placeholder value.
     * @return self
     */
    public function assign( $key, $value )
    {
        try {
            $key = $this->findKey( $key );
            $this->data[ $key ] = (string) $value;
        }
        catch ( RuntimeException $exc ) {
            $this->errors[] = $exc->getMessage();
        }

        return $this;
    }

    /**
     * Populate the template with data and reset the placeholder value array.
     * 
     * @param array $values (optional) Template values.
     * @return string|array The populated template(s).
     */
    public function render( array $values = [] )
    {
        // add last chance values
        foreach ( $values as $key => $value ) {
            $this->assign( $key, $value );
        }
        // set default values (if any)
        if ( false !== $this->defaultValue ) {
            $defaults = $this->getDefaultPlaceholders( $this->defaultValue );
            $this->data = array_replace( $defaults, $this->data );
        }
        // prepare arguments
        $placeholders = array_map( [$this, 'createPlaceholder'], array_keys( $this->data ) );
        $values = array_values( $this->data );
        // delete used data
        $this->data = [];

        return str_replace( $placeholders, $values, $this->tpl );
    }

    /**
     * Transform the internal placeholder name into the placeholders as 
     * expected in the template(s).
     * 
     * @param string $key Placeholder name.
     * @return string Template placeholder.
     */
    protected function createPlaceholder( $key )
    {
        return $this->open . $key . $this->close;
    }

    /**
     * Find all candidate placeholder names consisting of non-whitespace 
     * characters and set them into the placeholder array.
     * 
     * @return void
     * @throws RuntimeException Ambiguous placeholders found.
     */
    protected function setPlaceholderKeys()
    {
        $pattern = sprintf( '/%s(\S+?)%s/', preg_quote( $this->open, '/' ), preg_quote( $this->close, '/' ) );
        $count = preg_match_all( $pattern, $this->tpl, $matches, \PREG_SET_ORDER );

        if ( false === $count ) {
            throw new RuntimeException( 'Error parsing the template', preg_last_error() );
        }
        if ( 0 === $count ) {
            return;
        }

        $keys = array_column( $matches, 1 );
        $this->keys = array_unique( $keys );

        // check keys for ambiguity
        $lower_keys = array_map( function ( $value ) {
            return mb_strtolower( $value, 'UTF-8' );
        }, $this->keys );

        if ( count( $lower_keys ) > count( array_unique( $lower_keys ) ) ) {
            throw new RuntimeException( 'There are ambiguous placeholder names.' );
        }
    }

    /**
     * Create an array from all found keys and set a default value for each of them.
     * 
     * @param string $default Default value.
     * @return array Default data array.
     */
    protected function getDefaultPlaceholders( $default = '' )
    {
        $defaults = array_fill( 0, count( $this->keys ), (string) $default );
        return array_combine( $this->keys, $defaults );
    }

    /**
     * Get all encountered errors and clear the internal error array.
     * 
     * @return array List of error messages.
     */
    public function getErrors()
    {
        $errors = $this->errors;
        $this->errors = [];

        return $errors;
    }

    /**
     * Get the last error message. If no errors occurred FALSE is returned, 
     * which can be used to check if an error occurred at all.
     * 
     * @return string The last error message or FALSE.
     */
    public function getLastError()
    {
        return end( $this->errors );
    }
}
