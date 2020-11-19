<?php
/**
 * Created by PhpStorm.
 * User: ets-rebel
 * Date: 2/20/19
 * Time: 5:37 PM
 */

namespace App\Components;

use App\Campaign;
use App\CampaignTracking;
use App\Cache\CampaignTrackingCache;
use App\Helpers\CampaignValidation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CampaignEmailQueueComponents
{
    use InteractsWithMessages;
    /**
     * @var string
     */
    protected $type;

    /**
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $email_payload;

    /**
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $campaign;

    /**
     * TargetedUsers constructor.
     *
     * @param mixed $queue
     */

    public function __construct($payload = null)
    {
        $this->email_payload = $payload;
    }

    /**
     * Sending Email processing methods
     */
    public function process()
    {
        try {

            $trackingKey = (isset($this->email_payload['data']['track_key'])) ? $this->email_payload['data']['track_key'] : '';

            // Applying Campaign Validation Checks
            $validation = CampaignValidation::validation($this->email_payload['data']['id']);

            // Sending email message to the users
            $response = $this->sendEmail($this->email_payload['data']);

            // updating the campaign tracking status from executing to complete
            $trackinResponse=[];
            if ($response['status'] == "success") {

                // updating campaign tracking with status
                DB::update(" Update campaign_tracking SET status='".Campaign::CAMPAIGN_TRACKING_COMPLETED_STATUS."',
                                    message='Email sent successfully.', sent=1, sent_at='".Carbon::now()->format('Y-m-d h:i:s')."',
                                    ended_at='".Carbon::now()->format('Y-m-d h:i:s')."'
                                    where track_key = '".$trackingKey."' ");


                $trackinResponse = [
                    'status' => 'success',
                    'message' => 'Email sent successfully.'
                ];
            }
            elseif ($response['status'] == "error") {
                $this->email_payload['data']['email_body'] = (isset($this->email_payload['data']['email_body'])) ? addslashes($this->email_payload['data']['email_body']) : "";
                // updating campaign tracking with status
                DB::update("Update campaign_tracking SET status='" . Campaign::CAMPAIGN_TRACKING_FAILED_STATUS . "',
                                    message='". $response['message'] ."', sent_at='" . Carbon::now()->format('Y-m-d h:i:s') . "',
                                    ended_at='" . Carbon::now()->format('Y-m-d h:i:s') . "',
                                    payload='". \GuzzleHttp\json_encode($this->email_payload, true) ."'
                                    where track_key = '" . $trackingKey . "'");
            }

            // update tracking cache
            $last_sent_date = Carbon::now()->toDateTimeString();
            $sent_count = 1;
            $_tracking = new CampaignTrackingCache();
            $campaign_id = $this->email_payload['data']['id'];
            $row_id = $this->email_payload['data']['row_id'];
            $content = (isset($this->email_payload['data']['email_body'])) ? $this->email_payload['data']['email_body'] : "";
            $language =  (isset($this->email_payload['data']['language'])) ? $this->email_payload['data']['language'] : "en";
            $variant_id = (isset($this->email_payload['data']['variant_id'])) ? $this->email_payload['data']['variant_id'] : 1;
            $_tracking->updateCampaignTrackingCache($campaign_id, $row_id, $language, $variant_id, $content, $last_sent_date, $sent_count);

            return $trackinResponse;
        } catch (\Exception $exception) {

            $_track_key = (isset($this->email_payload['data']['track_key'])) ? $this->email_payload['data']['track_key'] : '';
            $this->email_payload['data']['email_body'] = (isset($this->email_payload['data']['email_body'])) ? addslashes($this->email_payload['data']['email_body']) : "";

            // updating campaign tracking with status
            DB::update("Update campaign_tracking SET status='" . Campaign::CAMPAIGN_TRACKING_FAILED_STATUS . "',
                                    message='". $exception->getMessage() ."', sent_at='" . Carbon::now()->format('Y-m-d h:i:s') . "',
                                    ended_at='" . Carbon::now()->format('Y-m-d h:i:s') . "',
                                    payload='". \GuzzleHttp\json_encode($this->email_payload, true) ."'
                                    where track_key = '" . $_track_key . "'");

            // update tracking cache
            $last_sent_date = Carbon::now()->toDateTimeString();
            $sent_count = 1;
            $_tracking = new CampaignTrackingCache();
            $campaign_id = $this->email_payload['data']['id'];
            $row_id = $this->email_payload['data']['row_id'];
            $content = (isset($this->email_payload['data']['email_body'])) ? $this->email_payload['data']['email_body'] : "";
            $language =  (isset($this->email_payload['data']['language'])) ? $this->email_payload['data']['language'] : "en";
            $variant_id = (isset($this->email_payload['data']['variant_id'])) ? $this->email_payload['data']['variant_id'] : 1;
            $_tracking->updateCampaignTrackingCache($campaign_id, $row_id, $language, $variant_id, $content, $last_sent_date, $sent_count);

            Log::error('Job Failed Exception: ' . $exception->getMessage());
            $response = [
                'status' => 'error',
                'message' => $exception->getMessage()
            ];
            return $response;
        }
    }
}