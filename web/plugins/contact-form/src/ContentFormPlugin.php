<?php

namespace Anchorcms\Plugins;

use Anchorcms\Plugin;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\Event;

class ContentFormPlugin extends Plugin
{
    public function getSubscribedEvents(EventDispatcher $dispatcher)
    {
        $dispatcher->addListener('routes', [$this, 'onRoutes']);
    }

    protected function onRoutes(Event $event)
    {
        $event->getRouter()->append('/test', []);
    }
}
