<?php
declare(strict_types=1);

namespace Activity\ActivityRecord;

use Activity\ActivityRecordPart\ClientMachine;
use Activity\ActivityRecordPart\DesktopId;
use Activity\ActivityRecordPart\Pid;
use Activity\ActivityRecordPart\WindowId;
use Activity\ActivityRecordPart\WindowTitle;
use Activity\ActivityRecordPart\WmClass;
use Activity\Settings;
use DateInterval;
use DateTimeInterface;

abstract class ActivityRecordAbstract implements ActivityRecordInterface
{
    /** @var string */
    protected $rawRecordFromFile;

    /** @var DateTimeInterface */
    protected $dateTime;

    /** @var WindowId */
    protected $windowId;

    /** @var DesktopId */
    protected $desktopId;

    /** @var Pid */
    protected $pid;

    /** @var WmClass */
    protected $wmClass;

    /** @var ClientMachine */
    protected $clientMachine;

    /** @var WindowTitle */
    protected $windowTitle;


    public function getRawRecordFromFile(): string
    {
        return $this->rawRecordFromFile;
    }

    public function getDateTime(): DateTimeInterface
    {
        return $this->dateTime;
    }

    public function getWindowId(): WindowId
    {
        return $this->windowId;
    }

    public function getDesktopId(): DesktopId
    {
        return $this->desktopId;
    }

    public function getPid(): Pid
    {
        return $this->pid;
    }

    public function getWmClass(): WmClass
    {
        return $this->wmClass;
    }

    public function getClientMachine(): ClientMachine
    {
        return $this->clientMachine;
    }

    public function getWindowTitle(): WindowTitle
    {
        return $this->windowTitle;
    }

    public function getDateTimeEndArtificial(): DateTimeInterface
    {
        $firstPossibleActivityDateTimeEnd = clone $this->dateTime;
        $firstPossibleActivityDateTimeEnd->add(new DateInterval(sprintf('PT%sS', Settings::MAX_ACTIVITY_RECORD_TIME_IN_SECONDS)));
        return $firstPossibleActivityDateTimeEnd;
    }

    public function __toString()
    {
        return $this->rawRecordFromFile;
    }

    abstract public function isUserActivity(): bool;
}
