<?php

namespace App\OpenApi;

use OpenApi\Analysers\AttributeAnnotationFactory;
use OpenApi\Analysers\DocBlockAnnotationFactory;
use OpenApi\Analysers\ReflectionAnalyser;

class SwaggerAnalyser extends ReflectionAnalyser
{
    public function __construct()
    {
        parent::__construct([
            new AttributeAnnotationFactory,
            new DocBlockAnnotationFactory,
        ]);
    }
}
