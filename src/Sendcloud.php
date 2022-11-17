<?php

namespace Todocoding\Sendcloud;


Class Sendcloud
{

  public function getshippingmethod(){

    $shippingMethods =null;
    $apiKey = config('sendcloud.consumer_key');
    $secretKey = config('sendcloud.consumer_secret');

        try {

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
                $shippingMethods = dd($data_string);//$this->getfilterShipping($data_string);
            }

        }  catch(\Exception $e) {
            $e->getMessage();
        }

        return $shippingMethods;
  }

  public function getfilterShipping($shippingMethods,$cartCounty){

    $selectedServices = [''];

    //$cart = Cart::getCart();
    //$cartCounty = $cart->shipping_address->country;

        // Print country wise prices for all enabled shipping methods
        foreach ($shippingMethods as $shippingMethod) {
            foreach ($shippingMethod as $key => $shippingServices) {
                if (in_array($shippingServices->id, $selectedServices)) {

                    foreach ($shippingServices->countries as $countryWiseShipping) {
                        if ($countryWiseShipping->iso_2 == $cartCounty) {

                            $price = $countryWiseShipping->price;

                            $services[$shippingServices->id] = [
                                'name' => $shippingServices->name,
                                'price' => $price,
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

    $billingAddress = $order;
    $shipmentId = $billingAddress['shipping_method'];

        $data['parcel'] = [
            "name" =>  $billingAddress['name'],
            "company_name" => $billingAddress['company_name'],
            "address" => $billingAddress['address1'],
            "house_number" => $billingAddress['address2'],
            "city" =>  $billingAddress['city'],
            "postal_code" => $billingAddress['postcode'],
            "telephone" => $billingAddress['phone'],
            "request_label" => true,
            "email" => $billingAddress['email'],
            "data" => [],
            "country" => $billingAddress['country'],
            "shipment" => [
                "id" => $shipmentId
            ],

            "weight" => $weight,
            "order_number" => $billingAddress['id'],
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

            return $result;
        } else {
            if (isset($resultArray->error)) {

                return $resultArray;
            } else {
                return null;
            }
        }
  }

  public function getLabelPdf($shipmentId,$labelFormate,$shipmentFromApi,$parcelId){

        $apiKey = config('sendcloud.consumer_key');
        $secretKey = config('sendcloud.consumer_secret');

        //$shipmentFromApi = Sendcloud::where('shipment_id' , $shipmentId)->first();

        //$parcelId = Sendcloud::where('shipment_id' , $shipmentId)->first()->parcel_id;

        if ($shipmentFromApi == null) {
            return 'manual_shipment';
        }

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

        $fileName = 'ShipmentLabel_' . $shipmentId . '_' . $labelFormate . '.pdf';

        $filepath = storage_path('app/public/shipping-label/') . $fileName ;

        if (Storage::exists($filepath) == false) {

            Storage::makeDirectory('shipping-label/');
        }

        $file = fopen($filepath, 'w+');
        fputs($file, $resultdata);

        fclose($file);

        return $filepath;
  }
}
