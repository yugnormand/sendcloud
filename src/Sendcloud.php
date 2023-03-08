<?php

namespace Todocoding\Sendcloud;

use Exception;
use Illuminate\Support\Facades\Storage;
use JsonException;

Class Sendcloud
{

  public function getshippingmethod($country,$model){

    $shippingMethods =null;
    $apiKey = config('sendcloud.consumer_key');
    $secretKey = config('sendcloud.consumer_secret');

    try{

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://panel.sendcloud.sc/api/v2/shipping_methods");
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERPWD, $apiKey.':'.$secretKey);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Accept: application/json',
                'Content-Type: application/json')
            );

            $errors = curl_error($ch);
            $result = curl_exec($ch);

            $data_string = json_decode($result);

            if (isset($data_string->error)) {
                throw new Exception($data_string->error->message);

            } else {
                $shippingMethods = $this->getfilterShipping($data_string,$country,$model);
            }

        }catch(\Exception $e) {
            $e->getMessage();
        }

        //dd($shippingMethods);
        return $shippingMethods;
  }

  public function getfilterShipping($shippingMethods,$cartCounty,$model){



        foreach ($shippingMethods as $shippingMethod) {

            foreach ($shippingMethod as $key => $shippingServices) {

                    foreach ($shippingServices->countries as $countryWiseShipping) {

                        if($model == 1){

                          if (($countryWiseShipping->name == $cartCounty) && ($shippingServices->max_weight <= 0.55 )) {

                            $price = $countryWiseShipping->price;
                            $services[$shippingServices->id] = [
                                'name' => $shippingServices->name,
                                'carrier' => $shippingServices->carrier,
                                'price' => $price,
                                'min_weight' =>$shippingServices->min_weight,
                                'max_weight' =>$shippingServices->max_weight,
                                'service_point_input' => $shippingServices->service_point_input
                            ];
                          }
                        }else{
                          if ($countryWiseShipping->name == $cartCounty) {

                            $price = $countryWiseShipping->price;
                            $services[$shippingServices->id] = [
                                'name' => $shippingServices->name,
                                'carrier' => $shippingServices->carrier,
                                'price' => $price,
                                'min_weight' =>$shippingServices->min_weight,
                                'max_weight' =>$shippingServices->max_weight,
                                'service_point_input' => $shippingServices->service_point_input
                            ];
                          }
                        }
                    }
            }
        }
        return $services;
  }

  public function createShipment($order, $weight){

    $apiKey = config('sendcloud.consumer_key');
    $secretKey = config('sendcloud.consumer_secret');

    $shipmentId = $order['shipping_id'];

        $data['parcel'] = [
            "name" => $order['name'],
            "company_name" => $order['company_name'],
            "address" => $order['address'],
            "house_number" => $order['address2'],
            "city" => $order['city'],
            "postal_code" => $order['postcode'],
            "telephone" => $order['phone'],
            "request_label" => true,
            "email" => $order['email'],
            "data" => [],
            "country" => $order['countrycode'],
            "shipment" => [
                "id" => $shipmentId
            ],

            "weight" => $weight,
            "order_number" => $order['id'],
        ];

        $data_string = json_encode($data);

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, "https://panel.sendcloud.sc/api/v2/parcels");
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $apiKey.':'.$secretKey);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json')
        );

        $result = curl_exec($ch);


        $resultArray = json_decode($result);


        if (isset($resultArray) && $resultArray != null && !isset($resultArray->error)) {

            return $resultArray;
        } else {
            if (isset($resultArray->error)) {

                return $resultArray;
            } else {
                return null;
            }
        }
  }

  public function getLabelPdf($shipmentId,$labelFormate,$parcelId){

        $apiKey = config('sendcloud.consumer_key');
        $secretKey = config('sendcloud.consumer_secret');

        if ($labelFormate == 4) {
            $url = 'https://panel.sendcloud.sc/api/v2/labels/label_printer/' . $parcelId;
        } else {
            $url = 'https://panel.sendcloud.sc/api/v2/labels/normal_printer/' . $parcelId . '?start_from=' . $labelFormate;
        }

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $apiKey.':'.$secretKey);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/pdf')
        );

        $resultdata = curl_exec($ch);

        $fileName = 'ShipmentLabel_' . uniqid() . '_' . $labelFormate . '.pdf';

        $filepath = base_path('storage/app/public/shipping-label/'. $fileName) ;

        if (Storage::exists($filepath) == false) {

            Storage::makeDirectory('public/shipping-label/');
        }

        $file = fopen($filepath, 'w+');
        fputs($file, $resultdata);

        fclose($file);

        return $fileName;
  }

  public function cancelShipment($parcelId){
        $apiKey = config('sendcloud.consumer_key');
        $secretKey = config('sendcloud.consumer_secret');

        $url = 'https://panel.sendcloud.sc/api/v2/parcels/'.$parcelId.'/cancel';

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $apiKey.':'.$secretKey);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json')
        );

        $resultdata = curl_exec($ch);

        return json_decode($resultdata);
  }

  public function trackShipment($tracknumber){
        $apiKey = config('sendcloud.consumer_key');
        $secretKey = config('sendcloud.consumer_secret');

        $url = 'https://panel.sendcloud.sc/api/v2/tracking/'.$tracknumber;

        $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERPWD, $apiKey.':'.$secretKey);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Accept: application/json',
            'Content-Type: application/json')
            );

        $resultdata = curl_exec($ch);
        $err = curl_error($ch);

        return json_decode($resultdata);
  }
}
