<?php
namespace Repository;

use BlocksEdit\Database\Repository;
use BlocksEdit\Database\Where;
use BlocksEdit\Database\WhereOr;
use BlocksEdit\System\Required;
use Entity\BillingPlan;
use Exception;
use DateTime;

/**
 * Class BillingPlanRepository
 */
class BillingPlanRepository extends Repository
{
    /**
     * @param int $id
     *
     * @return BillingPlan|null
     * @throws Exception
     */
    public function findByID(int $id): ?BillingPlan
    {
        return $this->findOne([
            'id' => $id
        ]);
    }

    /**
     * @return array
     * @throws Exception
     */
    public function findAll(): array
    {
        return $this->find([], null, null, ['dateCreated' => 'DESC']);
    }

    /**
     * @param int $oid
     *
     * @return BillingPlan|null
     * @throws Exception
     */
    public function findByOrg(int $oid): ?BillingPlan
    {
        return $this->findOne([
            'orgId' => $oid
        ]);
    }

    /**
     * @param string $type
     *
     * @return BillingPlan[]
     * @throws Exception
     */
    public function findByType(string $type): array
    {
        return $this->find([
            'type' => $type
        ]);
    }

    /**
     * @return BillingPlan[]
     * @throws Exception
     */
    public function findDueNow(): array
    {
        $month = (int)date('n');
        $day   = (int)date('d');
        $year  = (int)date('Y');

        return $this->find([
            'chargeMonth' => $month,
            'chargeDay'   => $day,
            'chargeYear'  => $year
        ]);
    }

    /**
     * @param int $days
     *
     * @return BillingPlan[]
     * @throws Exception
     */
    public function findDueInDays(int $days): array
    {
        $then = new DateTime("$days days");

        return $this->find([
            'chargeMonth' => $then->format('n'),
            'chargeDay'   => $then->format('d'),
            'chargeYear'  => $then->format('Y')
        ]);
    }

    /**
     * @param int $days
     *
     * @return BillingPlan[]
     * @throws Exception
     */
    public function findTrialEndingSoon(int $days): array
    {
        $then    = new DateTime("$days days");
        $whereOr = new WhereOr([
            BillingPlan::TYPE_TRIAL,
            BillingPlan::TYPE_TRIAL_INTEGRATION
        ]);

        return $this->find([
            new Where('type', '=', $whereOr),
            'chargeMonth'       => $then->format('n'),
            'chargeDay'         => $then->format('d'),
            'chargeYear'        => $then->format('Y'),
            'isTrialNoticeSent' => 0
        ]);
    }

    /**
     * @param int $days
     *
     * @return BillingPlan[]
     * @throws Exception
     */
    public function findDeclinedDaysAgo(int $days): array
    {
        $then = new DateTime("$days days ago");

        return $this->find([
            'DATE(dateDeclined)' => $then->format('Y-m-d')
        ]);
    }

    /**
     * @param DateTime $date
     * @param string   $type
     *
     * @return array
     * @throws Exception
     */
    public function findSince(DateTime $date, string $type): array
    {
        $stmt = $this->prepareAndExecute(
            sprintf(
                'SELECT * FROM `%s` WHERE `%s` > ? AND `%s` = ?',
                $this->meta->getTableName(),
                $this->entityAccessor->prefixColumnName('dateCreated'),
                $this->entityAccessor->prefixColumnName('type')
            ),
            [
                $date->format('Y-m-d H:i:s'),
                $type
            ]
        );

        return $this->fetchAll($stmt);
    }

    /**
     * @param string $type
     *
     * @return int
     * @throws Exception
     */
    public function countByType(string $type): int
    {
        $stmt = $this->prepareAndExecute(
            sprintf(
                'SELECT COUNT(*) FROM `%s` WHERE `%s` = ?',
                $this->meta->getTableName(),
                $this->entityAccessor->prefixColumnName('type')
            ),
            [
                $type
            ]
        );

        return (int)$stmt->fetchColumn();
    }

    /**
     * @param BillingPlan $billingPlan
     *
     * @return BillingPlan
     * @throws Exception
     */
    public function upgradeToTrial(BillingPlan $billingPlan): BillingPlan
    {
        $billingPlan
            ->setType(BillingPlan::TYPE_TRIAL)
            ->setChargeMonth($this->generateChargeMonth())
            ->setChargeYear($this->generateChargeYear())
            ->setIsTrialComplete(false)
            ->setIsUpcomingNoticeSent(false);
        if (!$billingPlan->getChargeDay()) {
            $billingPlan->setChargeDay($this->generateChargeDay());
        }
        if ($billingPlan->getId()) {
            $this->update($billingPlan);
        } else {
            $this->insert($billingPlan);
        }

        $this->billingLogRepository->createLog($billingPlan, 'User UPGRADED to TRIAL');

        return $billingPlan;
    }

    /**
     * @param BillingPlan $billingPlan
     *
     * @return BillingPlan
     * @throws Exception
     */
    public function upgradeToTrialIntegration(BillingPlan $billingPlan): BillingPlan
    {
        $billingPlan
            ->setType(BillingPlan::TYPE_TRIAL_INTEGRATION)
            ->setChargeMonth($this->generateChargeMonth())
            ->setChargeYear($this->generateChargeYear())
            ->setIsTrialComplete(false)
            ->setIsUpcomingNoticeSent(false);
        if (!$billingPlan->getChargeDay()) {
            $billingPlan->setChargeDay($this->generateChargeDay());
        }
        if ($billingPlan->getId()) {
            $this->update($billingPlan);
        } else {
            $this->insert($billingPlan);
        }

        $this->billingLogRepository->createLog($billingPlan, 'User UPGRADED to TRIAL INTEGRATION');

        return $billingPlan;
    }

    /**
     * @param BillingPlan $billingPlan
     *
     * @return BillingPlan
     * @throws Exception
     */
    public function upgradeToTeam(BillingPlan $billingPlan): BillingPlan
    {
        $billingPlan
            ->setType(BillingPlan::TYPE_TEAM)
            ->setChargeMonth($this->generateChargeMonth())
            ->setChargeYear($this->generateChargeYear())
            ->setIsTrialComplete(true)
            ->setIsUpcomingNoticeSent(false);
        if (!$billingPlan->getChargeDay()) {
            $billingPlan->setChargeDay($this->generateChargeDay());
        }
        if ($billingPlan->getId()) {
            $this->update($billingPlan);
        } else {
            $this->insert($billingPlan);
        }

        $this->billingLogRepository->createLog($billingPlan, 'User UPGRADED to TEAM');

        return $billingPlan;
    }

    /**
     * @param BillingPlan $billingPlan
     *
     * @return BillingPlan
     * @throws Exception
     */
    public function downgradeToSolo(BillingPlan $billingPlan): BillingPlan
    {
        $billingPlan
            ->setType(BillingPlan::TYPE_SOLO)
            ->setChargeMonth($this->generateChargeMonth())
            ->setChargeYear($this->generateChargeYear())
            ->setIsTrialComplete(true)
            ->setIsDowngraded(true)
            ->setIsUpcomingNoticeSent(false);
        if (!$billingPlan->getChargeDay()) {
            $billingPlan->setChargeDay($this->generateChargeDay());
        }
        if ($billingPlan->getId()) {
            $this->update($billingPlan);
        } else {
            $this->insert($billingPlan);
        }

        $this->billingLogRepository->createLog($billingPlan, 'User DOWNGRADED to SOLO');

        return $billingPlan;
    }

    /**
     * @param int $suggestion
     *
     * @return int
     */
    public function generateChargeDay(int $suggestion = 0): int
    {
        if (!$suggestion) {
            $suggestion = (int)date('d');
        }
        if ($suggestion > 28) {
            $suggestion = 1;
        }

        return $suggestion;
    }

    /**
     * @return int
     */
    public function generateChargeMonth(): int
    {
        $nextMonth = new DateTime('next month');

        return (int)$nextMonth->format('n');
    }

    /**
     * @return int
     */
    public function generateChargeYear(): int
    {
        $nextMonth = new DateTime('next month');

        return (int)$nextMonth->format('Y');
    }

    /**
     * @param BillingPlan $billingPlan
     * @param int         $days
     *
     * @return BillingPlan
     */
    public function addDaysToChargeDay(BillingPlan $billingPlan, int $days): BillingPlan
    {
        $newChargeDay   = $billingPlan->getChargeDay() + $days;
        $newChargeMonth = $billingPlan->getChargeMonth();
        $newChargeYear  = $billingPlan->getChargeYear();
        if ($newChargeDay > 28) {
            $newChargeDay = $newChargeDay - 28;
            $newChargeMonth += 1;
            if ($newChargeMonth > 12) {
                $newChargeMonth = 1;
                $newChargeYear += 1;
            }
        }

        $billingPlan->setChargeDay($newChargeDay);
        $billingPlan->setChargeMonth($newChargeMonth);
        $billingPlan->setChargeYear($newChargeYear);

        return $billingPlan;
    }

    /**
     * @var BillingLogRepository
     */
    protected $billingLogRepository;

    /**
     * @Required()
     * @param BillingLogRepository $billingLogRepository
     */
    public function setBillingLogRepository(BillingLogRepository $billingLogRepository)
    {
        $this->billingLogRepository = $billingLogRepository;
    }
}
