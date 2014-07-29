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

namespace Avoo\SerializerTranslation\Serializer\EventSubscriber;

use Avoo\SerializerTranslation\Configuration\Metadata\ClassMetadataInterface;
use Avoo\SerializerTranslation\Serializer\SerializationContext;
use Avoo\SerializerTranslation\Serializer\JsonSerializerInterface;
use JMS\Serializer\EventDispatcher\Events;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use Metadata\MetadataFactoryInterface;
use Symfony\Component\DependencyInjection\Container;

/**
 * @author Jérémy Jégou <jejeavo@gmail.com>
 */
class JsonEventSubscriber implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            array(
                'event'  => Events::PRE_SERIALIZE,
                'format' => 'json',
                'method' => 'onPreSerialize',
            ),
        );
    }

    /**
     * @var JsonSerializerInterface
     */
    private $jsonSerializer;

    /**
     * @var MetadataFactoryInterface
     */
    private $metadataFactory;

    /**
     * @var Container
     */
    private $container;

    /**
     * @param JsonSerializerInterface  $jsonSerializer
     * @param MetadataFactoryInterface $metadataFactory
     * @param Container                $container
     */
    public function __construct(
        JsonSerializerInterface $jsonSerializer,
        MetadataFactoryInterface $metadataFactory,
        Container $container
    ) {
        $this->jsonSerializer = $jsonSerializer;
        $this->metadataFactory = $metadataFactory;
        $this->container = $container;
    }

    public function onPreSerialize(ObjectEvent $event)
    {
        $object  = $event->getObject();
        $context = $event->getContext();

        if (!$context instanceof SerializationContext) {
            $context = new SerializationContext($context);
            $context->setLocale($this->container->getParameter('locale', $context->getLocale()));
        }

        /** @var ClassMetadataInterface $metadataClass */
        $metadataClass = $this->metadataFactory->getMetadataForClass(get_class($object));

        if (!is_null($metadataClass) && count($metadataClass->getPropertiesToTranslate())) {
            $this->jsonSerializer->trans($metadataClass, $event->getVisitor(), $context, $object);
        }
    }
}
