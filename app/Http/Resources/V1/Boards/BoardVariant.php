<?php

namespace App\Http\Resources\V1\Boards;

use App\Components\InteractsWithMessages;
use App\Board;

class BoardVariant
{
    use InteractsWithMessages;
    public function process($data, Board $board)
    {
        if (array_search(Board::STEP_BUILD, Board::STEP_LEVEL) > array_search($board->step, Board::STEP_LEVEL))
            $board->step = Board::STEP_BUILD;
        $boardVariant = new \App\BoardVariant();
        $boardRecord = $boardVariant->where('id', '=', $data['variant_id'])->get();
        if (count($boardRecord) > 0) {
            $boardVariant->where('id', '=', $data['variant_id'])->update([
                'variant_type' => $data['variantType'],
                'distribution' => $data['variantPercentage'],
                'from_name' => $data['from_name'],
                'from_email' => $data['from_email'],
                'subject' => $data['subject']
            ]);

        } else {
            $boardVariant->variant_type = $data['variantType'];
            $boardVariant->distribution = $data['variantPercentage'];
            $boardVariant->from_name = $data['from_name'];
            $boardVariant->from_email = $data['from_email'];
            $boardVariant->subject = $data['subject'];
            $boardVariant->board_id = $board->id;
            //    $boardVariant->variant_id = $data['variantNo'];
            $boardVariant->save();
        }
        $board->save();
        $boardVariant->refresh();
        $boardVariant->isFromEmailValid = $this->getFromEmailValidationStatus($data);
        return $boardVariant;
    }

    public function getFromEmailValidationStatus($boardData)
    {
        $status = true;

        if ($boardData['variantType'] == Board::BOARD_EMAIL_CODE && !empty(config('mail.verify_to_email'))) {
            $testFromEmail = $this->sendEmail([
                'email_from' => !empty($boardData['from_email']) ? $boardData['from_email'] : config('mail.from.address'),
                'email_from_name' => $boardData['from_name'],
                'to_email' => config('mail.verify_to_email'),
                'email_subject' => 'Verifying Email',
                'email_body' => 'This is just test email to verify ' . $boardData['from_email']
            ]);

            if ($testFromEmail['status'] == 'error') {
                $status = false;
            }
        }

        return $status;
    }
}