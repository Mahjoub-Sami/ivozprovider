<?php

namespace Ivoz\Provider\Domain\Model\OutgoingRouting;

use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\Common\Collections\Selectable;

interface OutgoingRoutingRepository extends ObjectRepository, Selectable {}

