<?php defined ('BASEPATH') OR exit ('No direct script access allowed');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2016 OA Wu Design
 * @link        http://www.ioa.tw/
 */
require_once FCPATH . 'vendor/autoload.php';

use LINE\LINEBot;
use LINE\LINEBot\Constant\EventSourceType;
use LINE\LINEBot\HTTPClient\CurlHTTPClient;
use LINE\LINEBot\Constant\HTTPHeader;
use LINE\LINEBot\Event\MessageEvent\TextMessage;
use LINE\LINEBot\Event\MessageEvent\VideoMessage;
use LINE\LINEBot\Event\MessageEvent\StickerMessage;
use LINE\LINEBot\Event\MessageEvent\LocationMessage;
use LINE\LINEBot\Event\MessageEvent\ImageMessage;
use LINE\LINEBot\Event\MessageEvent\AudioMessage;

class Callback extends Api_controller {

  public function __construct () {
    parent::__construct ();
    
  }
  public function test () {
    $limit = 10;
    $offset = 0;
    $conditions = array ('type IN (?)', array (1, 2));
    Keyword::find ('all', array ('select' => 'pattern, method', 'order' => 'weight DESC', 'include' => array ('contents'), 'limit' => $limit, 'offset' => $offset, 'conditions' => $conditions));
  }
  public function index () {
    $path = FCPATH . 'temp/input.json';
    $channel_id = Cfg::setting ('line', 'channel', 'id');
    $channel_secret = Cfg::setting ('line', 'channel', 'secret');
    $token = Cfg::setting ('line', 'channel', 'token');

    if (!isset ($_SERVER["HTTP_" . HTTPHeader::LINE_SIGNATURE])) {
      write_file ($path, '===> Error, Header Error!' . "\n", FOPEN_READ_WRITE_CREATE);
      exit ();
    }

    $httpClient = new CurlHTTPClient ($token);
    $bot = new LINEBot ($httpClient, ['channelSecret' => $channel_secret]);

    $signature = $_SERVER["HTTP_" . HTTPHeader::LINE_SIGNATURE];
    $body = file_get_contents ("php://input");

    try {
      $events = $bot->parseEventRequest ($body, $signature);
    } catch (Exception $e) {
      write_file ($path, '===> Error, Events Error! Msg:' . $e->getMessage () . "\n", FOPEN_READ_WRITE_CREATE);
      exit ();
    }

    foreach ($events as $event) {
      $instanceof = '';

      if ($event instanceof TextMessage) $instanceof = 'TextMessage';
      if ($event instanceof LocationMessage) $instanceof = 'LocationMessage';
      
      if ($event instanceof VideoMessage) $instanceof = 'VideoMessage';
      if ($event instanceof StickerMessage) $instanceof = 'StickerMessage';
      if ($event instanceof ImageMessage) $instanceof = 'ImageMessage';
      if ($event instanceof AudioMessage) $instanceof = 'AudioMessage';
      
      $params = array (
          'type' => $event->getType (),
          'instanceof' => $instanceof,
          'reply_token' => $event->getType () == 'unfollow' ? '' : $event->getReplyToken (),
          'source_id' => $event->getEventSourceId (),
          'source_type' => $event->isUserEvent() ? EventSourceType::USER : ($event->isGroupEvent () ? EventSourceType::GROUP : EventSourceType::ROOM),
          'timestamp' => $event->getTimestamp (),
          'message_type' => $event->getType () == 'message' ? $event->getMessageType () : '',
          'message_id' => $event->getType () == 'message' ? $event->getMessageId () : '',
          'status' => Log::STATUS_INIT,
        );
      if (!Log::transaction (function () use (&$log, $params) { return verifyCreateOrm ($log = Log::create ( array_intersect_key ($params, Log::table ()->columns))); })) return false;

      if ($event->getType () != 'message') continue;

      switch ($log->instanceof) {
        case 'TextMessage':
          $params = array (
              'log_id' => $log->id,
              'text' => $event->getText (),
            );
          if (!LogText::transaction (function () use (&$logText, $params) { return verifyCreateOrm ($logText = LogText::create ( array_intersect_key ($params, LogText::table ()->columns))); })) return false;
          $log->setStatus (Log::STATUS_CONTENT);

          // if ($logText->searchLocation ($bot) ||
          //     $logText->searchWeather ($bot) ||
          //     $logText->compare ($bot) ||
          //     false)
          //   echo 'Succeeded!';

          // if ($logText->searchIWantLook ($bot) ||
          //     $logText->searchIWantListen ($bot) ||
          //     $logText->searchIWantEat ($bot) ||
          //     $logText->searchRecommend ($bot) ||
          //     $logText->searchDont ($bot) ||
          //     $logText->search3Q ($bot) ||
          //     $logText->searchSpeechles ($bot) ||
          //     $logText->searchNotThing ($bot) ||
          //     $logText->searchHaha ($bot) ||
          //     $logText->searchBot ($bot) ||
          //     $logText->searchHello ($bot) ||
          //     $logText->searchName ($bot) ||
          //     $logText->searchCallMe ($bot) ||
          //     
          //     $logText->searchTest ($bot) ||
          //     false)
          //   echo 'Succeeded!';

          break;
        case 'LocationMessage':
          $params = array (
              'log_id' => $log->id,
              'title' => $event->getTitle (),
              'address' => $event->getAddress (),
              'latitude' => $event->getLatitude (),
              'longitude' => $event->getLongitude (),
            );
          if (!LogLocation::transaction (function () use (&$logLocation, $params) { return verifyCreateOrm ($logLocation = LogLocation::create ( array_intersect_key ($params, LogLocation::table ()->columns))); })) return false;
          $log->setStatus (Log::STATUS_CONTENT);

          if ($logLocation->searchProducts ($bot))
            echo 'Succeeded!';

          break;
        case 'StickerMessage':
          $params = array (
              'log_id' => $log->id,
              'package_id' => $event->getPackageId (),
              'sticker_id' => $event->getStickerId (),
            );
          if (!LogSticker::transaction (function () use (&$logSticker, $params) { return verifyCreateOrm ($logSticker = LogSticker::create ( array_intersect_key ($params, LogSticker::table ()->columns))); })) return false;
          $log->setStatus (Log::STATUS_CONTENT);
          break;

        case 'VideoMessage': $params = array ('log_id' => $log->id,); if (!LogVideo::transaction (function () use (&$logText, $params) { return verifyCreateOrm ($logText = LogVideo::create ( array_intersect_key ($params, LogVideo::table ()->columns))); })) return false; $log->setStatus (Log::STATUS_CONTENT); break;
        case 'ImageMessage': $params = array ('log_id' => $log->id,); if (!LogImage::transaction (function () use (&$logText, $params) { return verifyCreateOrm ($logText = LogImage::create ( array_intersect_key ($params, LogImage::table ()->columns))); })) return false; $log->setStatus (Log::STATUS_CONTENT); break;
        case 'AudioMessage': $params = array ('log_id' => $log->id,); if (!LogAudio::transaction (function () use (&$logText, $params) { return verifyCreateOrm ($logText = LogAudio::create ( array_intersect_key ($params, LogAudio::table ()->columns))); })) return false; $log->setStatus (Log::STATUS_CONTENT); break;
        default:
          break;
      }
    }
  }

  private function Get_Address_From_Google_Maps ($lat, $lng) {

  $url = 'http://maps.googleapis.com/maps/api/geocode/json?latlng=' . $lat . ',' . $lng . '&sensor=false&language=zh-TW';

  $data = @file_get_contents($url);
  $jsondata = json_decode($data,true);

  if (!$this->check_status ($jsondata)) return '';

  // $address = array(
  //     'country' => google_getCountry($jsondata),
  //     'province' => google_getProvince($jsondata),
  //     'city' => google_getCity($jsondata),
  //     'street' => google_getStreet($jsondata),
  //     'postal_code' => google_getPostalCode($jsondata),
  //     'country_code' => google_getCountryCode($jsondata),
  //     'formatted_address' => google_getAddress($jsondata),
  // );

  return $this->google_getAddress ($jsondata);
  }
  private function check_status ($jsondata) {
      if ($jsondata["status"] == "OK") return true;
      return false;
  }
  private function google_getAddress ($jsondata) {
      return $jsondata["results"][0]["formatted_address"];
  }
}
