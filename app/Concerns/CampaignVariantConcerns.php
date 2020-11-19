<?php

namespace App\Concerns;

use App\Lookup;

trait CampaignVariantConcerns
{
    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function type()
    {
        return $this->hasOne(Lookup::class, 'id', 'message_type_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function orientation()
    {
        return $this->hasOne(Lookup::class, 'id', 'orientation_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function position()
    {
        return $this->hasOne(Lookup::class, 'id', 'position_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function platform()
    {
        return $this->hasOne(Lookup::class, 'id', 'platform_id');
    }

    /**
     * @return bool
     */
    public function isTypeDialogue()
    {
        $type = $this->type;

        return in_array($type->code, [self::TYPE_DIALOGUE]) ? true : false;
    }

    /**
     * @return bool
     */
    public function isTypeBanner()
    {
        $type = $this->type;

        return in_array($type->code, [self::TYPE_BANNER]) ? true : false;
    }

    /**
     * @return bool
     */
    public function isTypeFullScreen()
    {
        $type = $this->type;

        return in_array($type->code, [self::TYPE_FULLSCREEN]) ? true : false;
    }

    /**
     * @return bool
     */
    public function isOrientationPortrait()
    {
        $orientation = $this->orientation;

        return in_array($orientation->code, [self::ORIENTATION_PORTRAIT]) ? true : false;
    }

    /**
     * @return bool
     */
    public function isOrientationLandscape()
    {
        $orientation = $this->orientation;

        return in_array($orientation->code, [self::ORIENTATION_LANDSCAPE]) ? true : false;
    }

    /**
     * @return bool
     */
    public function isPositionTop()
    {
        $position = $this->position;

        return in_array($position->code, [self::POSITION_TOP]) ? true : false;
    }

    /**
     * @return bool
     */
    public function isPositionMiddle()
    {
        $position = $this->position;

        return in_array($position->code, [self::POSITION_MIDDLE]) ? true : false;
    }

    /**
     * @return bool
     */
    public function isPositionBottom()
    {
        $position = $this->position;

        return in_array($position->code, [self::POSITION_BOTTOM]) ? true : false;
    }

    /**
     * @return bool
     */
    public function isPlatformAndroid()
    {
        $platform = $this->platform;

        return in_array($platform->code, [self::PLATFORM_ANDROID]) ? true : false;
    }

    /**
     * @return bool
     */
    public function isPlatformIOS()
    {
        $platform = $this->platform;

        return in_array($platform->code, [self::PLATFORM_IOS]) ? true : false;
    }

    /**
     * @return bool
     */
    public function isPlatformUniversal()
    {
        $platform = $this->platform;

        return in_array($platform->code, [self::PLATFORM_UNIVERSAL]) ? true : false;
    }
}