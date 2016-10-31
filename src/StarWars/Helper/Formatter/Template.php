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
     * Create instance.
     * 
     * If no closing delimiter is passed, the same string as for the opening 
     * delimiter is used.
     * 
     * @param string $template The template to populate.
     * @param string $tag Example placeholder.
     * @return self
     * @throws RuntimeException Ambiguous placeholders found.
     */
    public function __construct( $template, $tag )
    {
        $this->setDelimiters( $tag );
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
     * @param string $tag Example placeholder.
     * @return void
     * @throws RuntimeException Placeholder parse failure.
     */
    protected function setDelimiters( $tag )
    {
        if ( preg_match( '/(\W+)\w+(\W+)/', $tag, $match ) ) {
            list( $tag, $this->open, $this->close ) = $match;
        }
        else {
            $msg = 'Could not determine the placeholder delimiters';
            throw new RuntimeException( $msg );
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
     * @return string|false Key.
     */
    protected function findKey( $offset )
    {
        if ( in_array( $offset, $this->keys, true ) ) {
            return $offset;
        }
        // UTF-8
        $offset = mb_strtolower( $offset, 'UTF-8' );

        foreach ( $this->keys as $key ) {
            if ( mb_strtolower( $key, 'UTF-8' ) === $offset ) {
                return $key;
            }
        }

        return false;
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
        if ( false !== $offset = $this->findKey( $key ) ) {
            $this->data[ $offset ] = (string) $value;
        }

        return $this;
    }

    /**
     * Populate the template with data and reset the placeholder value array.
     * 
     * @param array $values (optional) Template values.
     * @return string The populated template.
     */
    public function render( array $values = [] )
    {
        // add last chance values
        foreach ( $values as $key => $value ) {
            $this->assign( $key, $value );
        }
        // set default values (if any)
        if ( false !== $this->defaultValue ) {
            $this->data += $this->getDefaultPlaceholders( $this->defaultValue );
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
}
