<?php

namespace Laravel\Dusk;

use Exception;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Facebook\WebDriver\WebDriverBy;
use Illuminate\Support\Traits\Macroable;

class ElementResolver
{
    use Macroable;

    /**
     * The remote web driver instance.
     *
     * @var \Facebook\WebDriver\Remote\RemoteWebDriver
     */
    public $driver;

    /**
     * The selector prefix for the resolver.
     *
     * @var string
     */
    public $prefix;

    /**
     * Set the elements the resolver should use as shortcuts.
     *
     * @var array
     */
    public $elements = [];

    /**
     * Create a new element resolver instance.
     *
     * @param  \Facebook\WebDriver\Remote\RemoteWebDriver  $driver
     * @param  string  $prefix
     * @return void
     */
    public function __construct($driver, $prefix = 'body')
    {
        $this->driver = $driver;
        $this->prefix = trim($prefix);
    }

    /**
     * Set the page elements the resolver should use as shortcuts.
     *
     * @param  array  $elements
     * @return $this
     */
    public function pageElements(array $elements)
    {
        $this->elements = $elements;

        return $this;
    }

    /**
     * Resolve the element for a given input "field".
     *
     * @param  string  $field
     * @return \Facebook\WebDriver\Remote\RemoteWebElement
     */
    public function resolveForTyping($field)
    {
        if (Str::startsWith($field, '#')) {
            return $this->driver->findElement(WebDriverBy::id(substr($field, 1)));
        }

        return $this->firstOrFail([
            $field, "input[name={$field}]", "textarea[name={$field}]"
        ]);
    }

    /**
     * Resolve the element for a given select "field".
     *
     * @param  string  $field
     * @return \Facebook\WebDriver\Remote\RemoteWebElement
     */
    public function resolveForSelection($field)
    {
        if (Str::startsWith($field, '#')) {
            return $this->driver->findElement(WebDriverBy::id(substr($field, 1)));
        }

        return $this->firstOrFail([
            $field, "select[name={$field}]"
        ]);
    }

    /**
     * Resolve the element for a given radio "field" / value.
     *
     * @param  string  $field
     * @param  string  $value
     * @return \Facebook\WebDriver\Remote\RemoteWebElement
     */
    public function resolveForRadioSelection($field, $value = null)
    {
        if (Str::startsWith($field, '#')) {
            return $this->driver->findElement(WebDriverBy::id(substr($field, 1)));
        }

        return $this->firstOrFail([
            $field, "input[type=radio][name={$field}][value={$value}]"
        ]);
    }

    /**
     * Resolve the element for a given checkbox "field".
     *
     * @param  string  $field
     * @return \Facebook\WebDriver\Remote\RemoteWebElement
     */
    public function resolveForChecking($field)
    {
        if (Str::startsWith($field, '#')) {
            return $this->driver->findElement(WebDriverBy::id(substr($field, 1)));
        }

        return $this->firstOrFail([
            $field, "input[type=checkbox][name={$field}]"
        ]);
    }

    /**
     * Resolve the element for a given file "field".
     *
     * @param  string  $field
     * @return \Facebook\WebDriver\Remote\RemoteWebElement
     */
    public function resolveForAttachment($field)
    {
        if (Str::startsWith($field, '#')) {
            return $this->driver->findElement(WebDriverBy::id(substr($field, 1)));
        }

        return $this->firstOrFail([
            $field, "input[type=file][name={$field}]"
        ]);
    }

    /**
     * Resolve the element for a given button.
     *
     * @param  string  $button
     * @return \Facebook\WebDriver\Remote\RemoteWebElement
     */
    public function resolveForButtonPress($button)
    {
        if (Str::startsWith($button, '#')) {
            return $this->driver->findElement(WebDriverBy::id(substr($button, 1)));
        }

        if (! is_null($element = $this->find($button))) {
            return $element;
        }

        if (! is_null($element = $this->find("input[type=submit][name={$button}]")) ||
            ! is_null($element = $this->find("button[name={$button}]"))) {
            return $element;
        }

        foreach ($this->all("input[type=submit]") as $element) {
            if ($element->getAttribute('value') === $button) {
                return $element;
            }
        }

        foreach ($this->all('button') as $element) {
            if (Str::contains($element->getText(), $button)) {
                return $element;
            }
        }

        throw new InvalidArgumentException(
            "Unable to locate button [{$button}]."
        );
    }

    /**
     * Find an element by the given selector or return null.
     *
     * @param  string  $selector
     * @return \Facebook\WebDriver\Remote\RemoteWebElement|null
     */
    public function find($selector)
    {
        try {
            return $this->findOrFail($selector);
        } catch (Exception $e) {
            //
        }
    }

    /**
     * Get the first element matching the given selectors.
     *
     * @param  array  $selectors
     * @return \Facebook\WebDriver\Remote\RemoteWebElement
     */
    public function firstOrFail($selectors)
    {
        foreach ((array) $selectors as $selector) {
            try {
                return $this->findOrFail($selector);
            } catch (Exception $e) {
                //
            }
        }

        throw $e;
    }

    /**
     * Find an element by the given selector or throw an exception.
     *
     * @param  string  $selector
     * @return \Facebook\WebDriver\Remote\RemoteWebElement
     */
    public function findOrFail($selector)
    {
        return $this->driver->findElement(
            WebDriverBy::cssSelector($this->format($selector))
        );
    }

    /**
     * Find the elements by the given selector or return an empty array.
     *
     * @param  string  $selector
     * @return array
     */
    public function all($selector)
    {
        try {
            return $this->driver->findElements(
                WebDriverBy::cssSelector($this->format($selector))
            );
        } catch (Exception $e) {
            //
        }

        return [];
    }

    /**
     * Format the given selector with the current prefix.
     *
     * @param  string  $selector
     * @return string
     */
    public function format($selector)
    {
        $selector = str_replace(
            array_keys($this->elements), array_values($this->elements), $selector
        );

        return trim($this->prefix.' '.$selector);
    }
}
