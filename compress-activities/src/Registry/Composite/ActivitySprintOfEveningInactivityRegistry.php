<?php
declare(strict_types=1);

namespace Activity\Registry\Composite;

use Activity\ActivityRecord\ActivityRecordInterface;
use Activity\ActivityRecord\ActivityRecordOnPowerOff;
use Activity\ActivityRecordAndSprintReset\ActivityRecordAndSprintReset;
use Activity\ActivityRecordWithDuration\ActivityRecordWithDuration;
use Activity\ActivitySprintWithDuration\ActivitySprintWithDurationRecord;
use Activity\Duration;
use Activity\Registry\ActivitySprintWithDurationRegistry;
use Activity\Settings;
use DateInterval;
use DateTime;
use DateTimeZone;

/**
 * Takes care of the very first inactivity which happens from 00:00 up to the time the first record is found.
 */
class ActivitySprintOfEveningInactivityRegistry
{
    /** @var ActivitySprintWithDurationRecord */
    private $data;

    public function __construct(ActivitySprintWithDurationRegistry $sprintRegistry, ActivityRecordInterface $lastActivityRecord)
    {
        // @TODO: Making 23:59:59 the latest because some systems may misunderstand the day change.
        // If misunderstanding will not be the case when connecting to other systems
        // then we may come back and change this to match the day entirely up to 24:00:00 of the same day, or 00:00:00 of the next day.
        $latestMidnightActivityDateTimeEnd = DateTime::createFromFormat('Y-m-d H:i:s', $lastActivityRecord->getDateTime()->format('Y-m-d 23:59:59'), new DateTimeZone(Settings::RECORD_DATETIME_TIMEZONE_FOR_PHP));
        $latestMidnightActivityDateTimeStart = $this->getPossibleActivityDateTimeStartWhenEndDateTimeIsKnown($latestMidnightActivityDateTimeEnd);

        $activitySprintWithFixedDurationOfLatestActivity = null;
        $activityRecordToAddLaterOn = $lastActivityRecord;

        // @TODOTEST
        if ($latestMidnightActivityDateTimeEnd <= $lastActivityRecord->getDateTimeEndArtificial()) {
            // Shorten the $lastActivityRecord time up to midnight as it cannot be longer for this day. Exceeding day time limit is not allowed.
            // No need to shorten in case $latestMidnightActivityDateTimeEnd and $lastActivityRecord->getDateTimeEndArtificial() is the same as the end result is the same too anyways.
            $activitySprintRecordOfLatestActivityWithDurationFixed = new ActivitySprintWithDurationRecord(
                $lastActivityRecord,
                new ActivityRecordWithDuration(
                    $lastActivityRecord,
                    new Duration(
                        $lastActivityRecord->getDateTime(),
                        $latestMidnightActivityDateTimeEnd
                    )
                ),
                null
            );

            // - Add the record to sprint (there will be no more records in the sprint for today)
            $sprintRegistry->add($activitySprintRecordOfLatestActivityWithDurationFixed);
        }

        // @TODOTEST
        if ($latestMidnightActivityDateTimeEnd > $lastActivityRecord->getDateTimeEndArtificial()) {
            // Register the existing current activity to the registry from $lastActivityRecord->getDateTime() up to $lastActivityRecord->getDateTimeEndArtificial()
            // - Add the record to sprint (there will be no more records in the sprint for today)
            $sprintRegistry->add(
                new ActivitySprintWithDurationRecord(
                    $lastActivityRecord,
                    new ActivityRecordWithDuration(
                        $lastActivityRecord,
                        new Duration(
                            $lastActivityRecord->getDateTime(),
                            $lastActivityRecord->getDateTimeEndArtificial()
                        )
                    ),
                    null
                )
            );

            // And add inactivity from $lastActivityRecord->getDateTimeEndArtificial() up to  $latestMidnightActivityDateTimeEnd
            $inanctivityRecord = new ActivityRecordOnPowerOff($lastActivityRecord->getDateTimeEndArtificial());
            $sprintRegistry->add(
                new ActivitySprintWithDurationRecord(
                    $inanctivityRecord,
                    new ActivityRecordWithDuration(
                        $inanctivityRecord,
                        new Duration(
                            $inanctivityRecord->getDateTime(),
                            $latestMidnightActivityDateTimeEnd
                        )
                    ),
                    null
                )
            );
        }
    }

    public function getData(): ?ActivitySprintWithDurationRecord
    {
        return $this->data;
    }

    private function getPossibleActivityDateTimeStartWhenEndDateTimeIsKnown(DateTime $endDateTime): DateTime
    {
        $firstPossibleActivityDateTimeStart = clone $endDateTime;
        $firstPossibleActivityDateTimeStart->sub(new DateInterval(sprintf('PT%sS', Settings::MAX_ACTIVITY_RECORD_TIME_IN_SECONDS)));
        return $firstPossibleActivityDateTimeStart;
    }

    private function getCurrentRecordWithArtificialDurationAddedToActivitySprint(ActivityRecordInterface $currentActivityRecord): ActivitySprintWithDurationRecord
    {
        $reset = new ActivityRecordAndSprintReset($currentActivityRecord);
        $activitySprintWithArtificialDurationOfNewActivity = new ActivitySprintWithDurationRecord(
            $currentActivityRecord,
            $reset->getActivityRecordWithDurationArtificial(),
            null
        );
        return $activitySprintWithArtificialDurationOfNewActivity;
    }
}
