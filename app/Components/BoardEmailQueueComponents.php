<?php
/**
 * Created by PhpStorm.
 * User: ets-rebel
 * Date: 2/20/19
 * Time: 5:37 PM
 */

namespace App\Components;

use App\Board;
use App\BoardTracking;
use App\Cache\BoardUserTrackingCache;
use App\Campaign;
use App\Helpers\BoardValidation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BoardEmailQueueComponents
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
    protected $board;

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

            // apply campaign validation checks
            $validation = BoardValidation::validation($this->email_payload['data']['id']);

            // Sending email message to the users
            $response = $this->sendEmail($this->email_payload['data']);

            // updating the campaign tracking status from executing to complete
            $trackinResponse=[];
            if ($response['status'] == "success") {

                // updating campaign tracking with status
                DB::update(" Update board_tracking SET status='".Board::BOARD_TRACKING_COMPLETED_STATUS ."',
                                    message='Email sent successfully.', sent=1, sent_at='".Carbon::now()->format('Y-m-d h:i:s')."',
                                    ended_at='".Carbon::now()->format('Y-m-d h:i:s')."'
                                    where track_key = '".$trackingKey."' ");

                $trackinResponse = [
                    'status' => 'success',
                    'message' => 'Email sent successfully.'
                ];
            }
            elseif ($response['status'] == "error") {
                // updating campaign tracking with status
                DB::update("Update board_tracking SET status='" . Board::BOARD_TRACKING_FAILED_STATUS . "',
                                    message='". $response['message'] ."', sent_at='" . Carbon::now()->format('Y-m-d h:i:s') . "',
                                    ended_at='" . Carbon::now()->format('Y-m-d h:i:s') . "',
                                    payload='". \GuzzleHttp\json_encode(htmlentities($this->email_payload, ENT_QUOTES), true) ."'
                                    where track_key = '" . $trackingKey . "'");
            }

            // update tracking cache
            $_tracking = new BoardUserTrackingCache();
            $board_id = $this->email_payload['data']['id'];
            $row_id = $this->email_payload['data']['row_id'];
            $variant_id = (isset($this->email_payload['data']['variant_id'])) ? $this->email_payload['data']['variant_id'] : 1;
            $variant_step_id = (isset($this->email_payload['data']['variant_step_id'])) ? $this->email_payload['data']['variant_step_id'] : 1;
            $last_sent_date = Carbon::now()->toDateTimeString();
            $_tracking->updateBoardTrackingCache($board_id, $row_id, $variant_id, $variant_step_id, $last_sent_date);

            return $trackinResponse;
        } catch (\Exception $exception) {

            $_track_key = (isset($this->email_payload['data']['track_key'])) ? $this->email_payload['data']['track_key'] : '';

            // updating campaign tracking with status
            DB::update("Update board_tracking SET status='" . Board::BOARD_TRACKING_FAILED_STATUS . "',
                                    message='". $exception->getMessage() ."', sent_at='" . Carbon::now()->format('Y-m-d h:i:s') . "',
                                    ended_at='" . Carbon::now()->format('Y-m-d h:i:s') . "',
                                    payload='". \GuzzleHttp\json_encode(htmlentities($this->email_payload, ENT_QUOTES), true) ."'
                                    where track_key = '" . $_track_key . "'");

            // update tracking cache
            $_tracking = new BoardUserTrackingCache();
            $board_id = $this->email_payload['data']['id'];
            $row_id = $this->email_payload['data']['row_id'];
            $variant_id = (isset($this->email_payload['data']['variant_id'])) ? $this->email_payload['data']['variant_id'] : 1;
            $variant_step_id = (isset($this->email_payload['data']['variant_step_id'])) ? $this->email_payload['data']['variant_step_id'] : 1;
            $last_sent_date = Carbon::now()->toDateTimeString();
            $_tracking->updateBoardTrackingCache($board_id, $row_id, $variant_id, $variant_step_id, $last_sent_date);
            Log::error('Job Failed Exception: ' . $exception->getMessage());

            $response = [
                'status' => 'error',
                'message' => $exception->getMessage()
            ];

            return $response;
        }
    }
}