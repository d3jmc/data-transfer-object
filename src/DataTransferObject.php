<?php

namespace D3jmc\DataTransferObject;

class DataTransferObject
{
    /**
     * @var array
     */
    protected array $mappings = [];

    public function __construct(
        array $attributes = [],
        array $mappings = [],
    ) {
        if (count($mappings) > 0) {
            $this->map($mappings);
        }
        if (count($attributes) > 0) {
            $this->fill($attributes);
        }
    }

    /**
     * @return self
     */
    public function get(): self
    {
        return $this;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return json_decode(json_encode($this->get()), true);
    }

    /**
     * @return array
     */
    public function toUnparsedArray(): array
    {
        return get_object_vars($this);
    }

    /**
     * @param array $attributes
     * @return self
     */
    public function map(array $attributes): self
    {
        $this->mappings = $attributes;

        return $this;
    }

    /**
     * @param array $attributes
     * @return self
     */
    public function fill(array $attributes): self
    {
        $this->populate($attributes);
        $this->populate(array_filter($this->toUnparsedArray(), [$this, 'isEmpty']));
        
        return $this;
    }

    /**
     * @param array $attributes
     * @return void
     */
    private function populate(array $attributes): void
    {
        if (count($attributes) == 0) {
            return;
        }

        foreach ($attributes as $key => $value) {
            $property = $this->toCamel(array_search($key, $this->mappings) ?: $key);

            if (!property_exists($this, $property)) {
                continue;
            }

            $this->setPropertyValue($key, $property, $value);
        }
    }

    /**
     * @param string $input
     * @return string
     */
    private function toCamel(string $input): string
    {
        return lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $input))));
    }

    /**
     * @param mixed $value
     * @return bool
     */
    private function isEmpty(mixed $value): bool
    {
        return (bool) !$value;
    }

    /**
     * @param string $key
     * @param string $property
     * @param mixed $value
     * @return void
     */
    private function setPropertyValue(string $key, string $property, mixed $value): void
    {
        $method = ucfirst($property);

        if (method_exists($this, "set$method")) {
            $this->{"set$method"}($value);
            return;
        }

        $mappings = is_array($this->mappings[$key] ?? '') ? $this->mappings[$key] : [];

        $reflectionProperty = new \ReflectionProperty($this, $property);
        $reflectionPropertyType = $reflectionProperty?->getType()?->getName() ?? '';
        $reflectionClass = false;

        if ($reflectionPropertyType === 'array') {
            preg_match('/@var array<(?<class>.*)>/m', $reflectionProperty->getDocComment(), $matches);

            if (isset($matches[1])) {
                $reflectionClass = $this->createReflectionClass($matches[1], [$this, 'fillReflectionClasses'], $property, $value, $mappings);
            }
        } else {
            $reflectionClass = $this->createReflectionClass($reflectionPropertyType, [$this, 'fillReflectionClass'], $property, $value, $mappings);
        }

        if (!$reflectionClass) {
            $this->{$property} = $value;
        }
    }

    /**
     * @param string $namespace
     * @param mixed $callback
     * @param string $property
     * @param mixed $value
     * @param array $mappings
     * @return bool
     */
    private function createReflectionClass(string $namespace, mixed $callback, string $property, mixed $value, array $mappings): bool
    {
        if (class_exists($namespace)) {
            $class = new $namespace();
            if ($class instanceof DataTransferObject) {
                $callback($class, $property, $value, $mappings);
                return true;
            }
        }
        return false;
    }

    /**
     * @param DataTransferObject $reflectionClass
     * @param string $property
     * @param array $collection
     * @param array $mappings
     * @return void
     */
    private function fillReflectionClasses(DataTransferObject $reflectionClass, string $property, array $collection, array $mappings): void
    {
        foreach ($collection as $attributes) {
            $this->{$property}[] = new $reflectionClass($attributes, $mappings);
        }
    }

    /**
     * @param DataTransferObject $reflectionClass
     * @param string $property
     * @param array $attributes
     * @param array $mappings
     * @return void
     */
    private function fillReflectionClass(DataTransferObject $reflectionClass, string $property, array $attributes, array $mappings): void
    {
        $reflectionClass->map($mappings)->fill($attributes);

        $this->{$property} = $reflectionClass;
    }
}