<?php

namespace App\Jobs\Admin;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AdminPhoneOtpJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $details;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($details)
    {
        $this->details = $details;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $msg="Your Investible access code is ".$this->details['otp'];
        $this->notify($this->details['phone'], $msg);
    }

    public function notify($phone, $msg){
        $curl = curl_init();
        $data = array("api_key" => env('TERMII'), "to" => $phone,  "from" => "Gavice NG",
        "sms" => $msg,  "type" => "plain",  "channel" => "dnd" );
        $post_data = json_encode($data);
        curl_setopt_array($curl, array(
        CURLOPT_URL => "https://api.ng.termii.com/api/sms/send",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => $post_data,
        CURLOPT_HTTPHEADER => array(
        "Content-Type: application/json"
        ),
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        $responseBody= json_decode($response, true);
        return $responseBody;
    }
}
