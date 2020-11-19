<?php

namespace App\Components;


class PayloadNotification
{
    /**
     * @internal
     *
     * @var null|string
     */
    protected $title;
    /**
     * @internal
     *
     * @var null|string
     */
    protected $link;
    /**
     * @internal
     *
     * @var null|string
     */
    protected $body;

    /**
     * @internal
     *
     * @var null|string
     */
    protected $icon;

    /**
     * @internal
     *
     * @var null|string
     */
    protected $sound;

    /**
     * @internal
     *
     * @var null|string
     */
    protected $channelId;

    /**
     * @internal
     *
     * @var null|string
     */
    protected $badge;

    /**
     * @internal
     *
     * @var null|string
     */
    protected $tag;

    /**
     * @internal
     *
     * @var null|string
     */
    protected $color;

    /**
     * @internal
     *
     * @var null|string
     */
    protected $clickAction;

    /**
     * @internal
     *
     * @var null|string
     */
    protected $bodyLocationKey;

    /**
     * @internal
     *
     * @var null|string
     */
    protected $bodyLocationArgs;

    /**
     * @internal
     *
     * @var null|string
     */
    protected $titleLocationKey;

    /**
     * @internal
     *
     * @var null|string
     */
    protected $titleLocationArgs;

    /**
     * Title must be present on android notification and ios (watch) notification.
     *
     * @param string $title
     */
    public function __construct($title = null)
    {
        $this->title = $title;
    }

    /**
     * Indicates notification title. This field is not visible on iOS phones and tablets.
     * but it is required for android.
     *
     * @param string $title
     *
     * @return PayloadNotification
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    public function setLink($link)
    {
        $this->link = $link;

        return $this;
    }

    /**
     * Indicates notification body text.
     *
     * @param string $body
     *
     * @return PayloadNotification
     */
    public function setBody($body)
    {
        $this->body = $body;

        return $this;
    }

    /**
     * Set a channel ID for android API >= 26.
     *
     * @param string $channelId
     *
     * @return PayloadNotification
     */
    public function setChannelId($channelId)
    {
        $this->channelId = $channelId;

        return $this;
    }

    /**
     * Supported Android
     * Indicates notification icon. example : Sets value to myicon for drawable resource myicon.
     *
     * @param string $icon
     *
     * @return PayloadNotification
     */
    public function setIcon($icon)
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * Indicates a sound to play when the device receives a notification.
     * Supports default or the filename of a sound resource bundled in the app.
     *
     * @param string $sound
     *
     * @return PayloadNotification
     */
    public function setSound($sound)
    {
        $this->sound = $sound;

        return $this;
    }

    /**
     * Supported Ios.
     *
     * Indicates the badge on the client app home icon.
     *
     * @param string $badge
     *
     * @return PayloadNotification
     */
    public function setBadge($badge)
    {
        $this->badge = $badge;

        return $this;
    }

    /**
     * Supported Android.
     *
     * Indicates whether each notification results in a new entry in the notification drawer on Android.
     * If not set, each request creates a new notification.
     * If set, and a notification with the same tag is already being shown, the new notification replaces the existing one in the notification drawer.
     *
     * @param string $tag
     *
     * @return PayloadNotification
     */
    public function setTag($tag)
    {
        $this->tag = $tag;

        return $this;
    }

    /**
     * Supported Android.
     *
     * Indicates color of the icon, expressed in #rrggbb format
     *
     * @param string $color
     *
     * @return PayloadNotification
     */
    public function setColor($color)
    {
        $this->color = $color;

        return $this;
    }

    /**
     * Indicates the action associated with a user click on the notification.
     *
     * @param string $action
     *
     * @return PayloadNotification
     */
    public function setClickAction($action)
    {
        $this->clickAction = $action;

        return $this;
    }

    /**
     * Indicates the key to the title string for localization.
     *
     * @param string $titleKey
     *
     * @return PayloadNotification
     */
    public function setTitleLocationKey($titleKey)
    {
        $this->titleLocationKey = $titleKey;

        return $this;
    }

    /**
     * Indicates the string value to replace format specifiers in the title string for localization.
     *
     * @param mixed $titleArgs
     *
     * @return PayloadNotification
     */
    public function setTitleLocationArgs($titleArgs)
    {
        $this->titleLocationArgs = $titleArgs;

        return $this;
    }

    /**
     * Indicates the key to the body string for localization.
     *
     * @param string $bodyKey
     *
     * @return PayloadNotification
     */
    public function setBodyLocationKey($bodyKey)
    {
        $this->bodyLocationKey = $bodyKey;

        return $this;
    }

    /**
     * Indicates the string value to replace format specifiers in the body string for localization.
     *
     * @param mixed $bodyArgs
     *
     * @return PayloadNotification
     */
    public function setBodyLocationArgs($bodyArgs)
    {
        $this->bodyLocationArgs = $bodyArgs;

        return $this;
    }

    /**
     * Get title.
     *
     * @return null|string
     */
    public function getTitle()
    {
        return $this->title;
    }

    public function getLink()
    {
        return $this->link;
    }

    /**
     * Get body.
     *
     * @return null|string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Get channel id for android api >= 26
     *
     * @return null|string
     */
    public function getChannelId()
    {
        return $this->channelId;
    }

    /**
     * Get Icon.
     *
     * @return null|string
     */
    public function getIcon()
    {
        return $this->icon;
    }

    /**
     * Get Sound.
     *
     * @return null|string
     */
    public function getSound()
    {
        return $this->sound;
    }

    /**
     * Get Badge.
     *
     * @return null|string
     */
    public function getBadge()
    {
        return $this->badge;
    }

    /**
     * Get Tag.
     *
     * @return null|string
     */
    public function getTag()
    {
        return $this->tag;
    }

    /**
     * Get Color.
     *
     * @return null|string
     */
    public function getColor()
    {
        return $this->color;
    }

    /**
     * Get ClickAction.
     *
     * @return null|string
     */
    public function getClickAction()
    {
        return $this->clickAction;
    }

    /**
     * Get BodyLocationKey.
     *
     * @return null|string
     */
    public function getBodyLocationKey()
    {
        return $this->bodyLocationKey;
    }

    /**
     * Get BodyLocationArgs.
     *
     * @return null|string|array
     */
    public function getBodyLocationArgs()
    {
        return $this->bodyLocationArgs;
    }

    /**
     * Get TitleLocationKey.
     *
     * @return string
     */
    public function getTitleLocationKey()
    {
        return $this->titleLocationKey;
    }

    /**
     * GetTitleLocationArgs.
     *
     * @return null|string|array
     */
    public function getTitleLocationArgs()
    {
        return $this->titleLocationArgs;
    }

    /**
     * Build an PayloadNotification.
     *
     * @return PayloadNotificationDataBinding
     */
    public function build()
    {
        return new PayloadNotificationDataBinding($this);
    }
}