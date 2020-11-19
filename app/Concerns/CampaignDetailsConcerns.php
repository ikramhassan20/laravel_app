<?php

namespace App\Concerns;

trait CampaignDetailsConcerns
{
    /**
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function trigger_actions()
    {
        return $this->actions->filter(function ($action) {
            return ($action->isActionTypeTrigger() === true) ? $action : null;
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function conversion_actions()
    {
        return $this->actions->filter(function ($action) {
            return ($action->isActionTypeConversion() === true) ? $action : null;
        });
    }

    /**
     * @return bool
     */
    public function isTypeEmail()
    {
        $platform = $this->campaign_type;

        return in_array($platform->code, [self::TYPE_EMAIL]) ? true : false;
    }

    /**
     * @return bool
     */
    public function isTypeInApp()
    {
        $platform = $this->campaign_type;

        return in_array($platform->code, [self::TYPE_INAPP]) ? true : false;
    }

    /**
     * @return bool
     */
    public function isTypePush()
    {
        $platform = $this->campaign_type;

        return in_array($platform->code, [self::TYPE_PUSH]) ? true : false;
    }

    /**
     * @return bool
     */
    public function isDraft()
    {
        return in_array($this->status, [self::STATUS_DRAFT]) ? true : false;
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return in_array($this->status, [self::STATUS_ACTIVE]) ? true : false;
    }

    /**
     * @return bool
     */
    public function isSuspended()
    {
        return in_array($this->status, [self::STATUS_SUSPENDED]) ? true : false;
    }

    /**
     * @return bool
     */
    public function isExpired()
    {
        return in_array($this->status, [self::STATUS_EXPIRED]) ? true : false;
    }

    /**
     * @return bool
     */
    public function isDeliveryTypeSchedule()
    {
        return in_array($this->delivery_type, [self::DELIVERY_TYPE_SCHEDULE]) ? true : false;
    }

    /**
     * @return bool
     */
    public function isDeliveryTypeAction()
    {
        return in_array($this->delivery_type, [self::DELIVERY_TYPE_ACTION]) ? true : false;
    }

    /**
     * @return bool
     */
    public function isDeliveryTypeApi()
    {
        return in_array($this->delivery_type, [self::DELIVERY_TYPE_API]) ? true : false;
    }

    /**
     * @return bool
     */
    public function isDeliveryControlEnabled()
    {
        return (isset($this->delivery_control) && ((bool)$this->delivery_control === true)) ? true : false;
    }

    /**
     * @return bool
     */
    public function isPriorityLow()
    {
        return in_array($this->priority, [self::PRIORITY_LOW]) ? true : false;
    }

    /**
     * @return bool
     */
    public function isPriorityMedium()
    {
        return in_array($this->priority, [self::PRIORITY_MEDIUM]) ? true : false;
    }

    /**
     * @return bool
     */
    public function isPriorityHigh()
    {
        return in_array($this->priority, [self::PRIORITY_HIGH]) ? true : false;
    }

    /**
     * @return bool
     */
    public function isCappingEnabled()
    {
        return ((bool)$this->capping === true) ? true : false;
    }
}