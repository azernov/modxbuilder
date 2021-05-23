<?php
/**
 * @var modxBuilder $this
 * @var string $categoryName
 * @var string $namespace
 */

$events = array();

$realEvents = $this->modx->getCollection('modEvent', array(
    'groupname' => $categoryName
));

if(!$realEvents) return $events;

/** @var modEvent[] $realEvents */
foreach($realEvents as $realEvent){
    /** @var modEvent $event */
    $event = $this->modx->newObject('modEvent');
    $eventData = $realEvent->toArray();
    $event->fromArray($eventData,'',true);
    $events[] = $event;
}

unset($realEvents,$eventData);

return $events;
