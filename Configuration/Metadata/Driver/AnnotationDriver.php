<?php

/*
* The MIT License (MIT)
*
* Copyright (c) 2014 J. Jégou <jejeavo@gmail.com>
*
* Permission is hereby granted, free of charge, to any person obtaining a copy
* of this software and associated documentation files (the "Software"), to deal
* in the Software without restriction, including without limitation the rights
* to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
* copies of the Software, and to permit persons to whom the Software is
* furnished to do so, subject to the following conditions:
*
* The above copyright notice and this permission notice shall be included in all
* copies or substantial portions of the Software.
*
* THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
* IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
* FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
* AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
* LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
* OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
* SOFTWARE.
*/

namespace Avoo\SerializerTranslation\Configuration\Metadata\Driver;

use Doctrine\Common\Annotations\Reader as AnnotationsReader;
use Hateoas\Configuration\Annotation;
use Hateoas\Configuration\Embedded;
use Hateoas\Configuration\Exclusion;
use Hateoas\Configuration\Metadata\ClassMetadata;
use Hateoas\Configuration\Relation;
use Hateoas\Configuration\RelationProvider;
use Hateoas\Configuration\Route;
use Metadata\Driver\DriverInterface;

/**
 * @author Jérémy Jégou <jejeavo@gmail.com>
 */
class AnnotationDriver implements DriverInterface
{
    /**
     * @var AnnotationsReader
     */
    private $reader;

    /**
     * @param AnnotationsReader $reader
     */
    public function __construct(AnnotationsReader $reader)
    {
        $this->reader = $reader;
    }

    /**
     * {@inheritdoc}
     */
    public function loadMetadataForClass(\ReflectionClass $class)
    {
        $annotations = $this->reader->getClassAnnotations($class);

        if (0 === count($annotations)) {
            return null;
        }

        $classMetadata = new ClassMetadata($class->getName());
        $classMetadata->fileResources[] = $class->getFilename();

        foreach ($annotations as $annotation) {
            if ($annotation instanceof Annotation\Relation) {
                $classMetadata->addRelation(new Relation(
                    $annotation->name,
                    $this->createHref($annotation->href),
                    $this->createEmbedded($annotation->embedded),
                    $annotation->attributes ?: array(),
                    $this->createExclusion($annotation->exclusion)
                ));
            } elseif ($annotation instanceof Annotation\RelationProvider) {
                $classMetadata->addRelationProvider(new RelationProvider($annotation->name));
            }
        }

        if (0 === count($classMetadata->getRelations()) && 0 === count($classMetadata->getRelationProviders())) {
            return null;
        }

        return $classMetadata;
    }

    private function parseExclusion(Annotation\Exclusion $exclusion)
    {
        return new Exclusion(
            $exclusion->groups,
            $exclusion->sinceVersion,
            $exclusion->untilVersion,
            $exclusion->maxDepth,
            $exclusion->excludeIf
        );
    }

    private function createHref($href)
    {
        if ($href instanceof Annotation\Route) {
            $href = new Route($href->name, $href->parameters, $href->absolute, $href->generator);
        }

        return $href;
    }

    private function createEmbedded($embedded)
    {
        if ($embedded instanceof Annotation\Embedded) {
            $embeddedExclusion = $embedded->exclusion;

            if (null !== $embeddedExclusion) {
                $embeddedExclusion = $this->parseExclusion($embeddedExclusion);
            }

            $embedded = new Embedded($embedded->content, $embedded->xmlElementName, $embeddedExclusion);
        }

        return $embedded;
    }

    private function createExclusion($exclusion)
    {
        if (null !== $exclusion) {
            $exclusion = $this->parseExclusion($exclusion);
        }

        return $exclusion;
    }
}
