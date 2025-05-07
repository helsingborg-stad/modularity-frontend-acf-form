<?php

namespace ModularityFrontendForm\FieldMapping\Director;
use ModularityFrontendForm\FieldMapping\Mapper\Interfaces\FieldMapperInterface;

interface FieldMappingDirectorInterface
{
    public function resolveMapper(array $field): FieldMapperInterface;
}