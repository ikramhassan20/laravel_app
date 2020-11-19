<?php

namespace App;

use App\Concerns\CampaignConcerns;
use App\Concerns\CampaignVariantConcerns;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CampaignVariant extends Model
{
    use CampaignConcerns, CampaignVariantConcerns;

    protected $table = 'campaign_variant';

    protected $fillable = [
        'campaign_id',
        'message_type_id',
        'orientation_id',
        'position_id',
        'platform_id',
        'created_at',
        'updated_at'
    ];

    const TYPE_BANNER = 'banner';
    const TYPE_FULLSCREEN = 'fullscreen';
    const TYPE_DIALOGUE = 'dialogue';

    const ORIENTATION_PORTRAIT = 'portrait';
    const ORIENTATION_LANDSCAPE = 'landscape';

    const POSITION_TOP = 'top';
    const POSITION_MIDDLE = 'middle';
    const POSITION_BOTTOM = 'bottom';

    const PLATFORM_ANDROID = 'android';
    const PLATFORM_IOS = 'ios';
    const PLATFORM_UNIVERSAL = 'universal';

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function translations()
    {
        return $this->morphMany(Translation::class, 'translatable');
    }
}
